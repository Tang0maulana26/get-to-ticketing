<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetBuyerDataRequest;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuyerResetController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index()
    {
        return view('admin.reset.index', [
            'orderCount' => Order::count(),
            'pendingCount' => Order::where('status', 'pending')->count(),
            'paidCount' => Order::where('status', 'paid')->count(),
            'ticketCount' => Ticket::count(),
            'revenue' => Order::where('status', 'paid')->sum('total'),
            'buyerCount' => User::where('role', 'customer')->whereHas('orders')->count(),
        ]);
    }

    public function resetAll(ResetBuyerDataRequest $request)
    {
        DB::transaction(function () {
            // 1. Release any pending reservations
            $this->bookings->expire();

            // 2. Safely wipe buyer transaction records
            Ticket::query()->delete();
            OrderItem::query()->delete();
            Order::query()->delete();

            // 3. Reset ticket categories stock to full capacity
            TicketCategory::query()->update([
                'stock' => DB::raw('capacity'),
            ]);

            // 4. Reset discount code usage counters
            Discount::query()->update([
                'used_count' => 0,
            ]);
        }, 3);

        return to_route('admin.reset.index')
            ->with('success', 'Seluruh data transaksi dan tiket pembeli berhasil dibersihkan dengan aman. Stok tiket telah dipulihkan ke kapasitas penuh.');
    }

    public function resetCustomer(Request $request, User $user)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        DB::transaction(function () use ($user) {
            $orders = Order::where('user_id', $user->id)->with('items')->lockForUpdate()->get();

            foreach ($orders as $order) {
                if ($order->status === 'pending') {
                    $this->bookings->release($order, 'cancelled');
                } else {
                    // For paid/cancelled orders, restore stock if they were paid so inventory remains accurate
                    if ($order->status === 'paid') {
                        foreach ($order->items as $item) {
                            TicketCategory::whereKey($item->ticket_category_id)
                                ->lockForUpdate()
                                ->first()
                                ?->increment('stock', $item->quantity);
                        }
                    }

                    if ($order->discount_id && $order->status === 'paid') {
                        $discount = Discount::whereKey($order->discount_id)->lockForUpdate()->first();
                        if ($discount && $discount->used_count > 0) {
                            $discount->decrement('used_count');
                        }
                    }
                }

                $itemIds = $order->items->pluck('id');
                if ($itemIds->isNotEmpty()) {
                    Ticket::whereIn('order_item_id', $itemIds)->delete();
                    OrderItem::whereIn('id', $itemIds)->delete();
                }

                $order->delete();
            }
        }, 3);

        return to_route('admin.users.index')
            ->with('success', "Seluruh riwayat pesanan pelanggan {$user->name} berhasil dibersihkan dan stok tiket telah disesuaikan.");
    }
}

