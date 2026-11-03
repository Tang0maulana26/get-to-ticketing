<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Discount;
use App\Models\Order;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->where('status', 'paid')], 'total')
            ->latest();

        if ($request->filled('q')) {
            $search = '%' . trim($request->q) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('username', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'totalAdmins' => User::where('role', 'admin')->count(),
            'totalCustomers' => User::where('role', 'customer')->count(),
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user->loadCount('orders'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        // Safety guard: Admin cannot demote their own account
        if (auth()->id() === $user->id && $request->role !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mencabut status admin dari akun sendiri.']);
        }

        // Safety guard: Must always maintain at least one admin
        if ($user->role === 'admin' && $request->role === 'customer' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Tidak dapat mengubah peran admin terakhir di sistem.']);
        }

        $user->name = $request->validated('name');
        if ($request->filled('username')) {
            $user->username = strtolower(trim($request->validated('username')));
        }
        $user->email = strtolower(trim($request->validated('email')));
        $user->role = $request->validated('role');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->validated('password'));
        }

        $user->save();

        return to_route('admin.users.index')
            ->with('success', "Data pengguna {$user->name} ({$user->email}) berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus admin terakhir di sistem.']);
        }

        $userName = $user->name;

        DB::transaction(function () use ($user) {
            $orders = Order::where('user_id', $user->id)->with('items')->lockForUpdate()->get();

            foreach ($orders as $order) {
                if ($order->status === 'pending') {
                    app(BookingService::class)->release($order, 'cancelled');
                } else {
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
                    \App\Models\Ticket::whereIn('order_item_id', $itemIds)->delete();
                    \App\Models\OrderItem::whereIn('id', $itemIds)->delete();
                }

                $order->delete();
            }

            $user->delete();
        }, 3);

        return to_route('admin.users.index')
            ->with('success', "Pengguna {$userName} beserta riwayat transaksinya berhasil dihapus.");
    }
}
