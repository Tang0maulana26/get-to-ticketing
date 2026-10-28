<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Event;
use App\Models\Order;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request)
    {
        // Only this customer's reservations; the scheduler handles everyone else.
        $this->bookings->expire(user: $request->user());

        return view('orders.index', [
            'orders' => $request->user()->orders()->with('items')->latest()->paginate(10),
        ]);
    }

    public function store(CheckoutRequest $request, Event $event)
    {
        $order = $this->bookings->checkout(
            $request->user(),
            $event,
            $request->validated('tickets'),
            $request->validated('checkout_key'),
        );

        return to_route('orders.show', $order)
            ->with('success', 'Tiket disimpan sementara. Selesaikan pembayaran dalam '.config('ticketing.reservation_minutes').' menit.');
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);
        if ($order->status === 'pending' && $order->expires_at->isPast()) {
            $this->bookings->release($order, 'expired');
        }

        return view('orders.show', ['order' => $order->refresh()->load('items.tickets')]);
    }

    public function cancel(Order $order)
    {
        Gate::authorize('update', $order);
        $this->bookings->release($order);

        $message = $order->refresh()->status === 'cancelled'
            ? 'Pesanan dibatalkan dan stok tiket dikembalikan.'
            : 'Pesanan ini tidak dapat dibatalkan.';

        return to_route('orders.show', $order)->with('success', $message);
    }

    public function pay(Order $order)
    {
        Gate::authorize('update', $order);
        abort_unless(app()->environment('local', 'testing') && config('ticketing.demo_payments'), 403);
        $this->bookings->payDemo($order);

        return to_route('orders.show', $order)->with('success', 'Simulasi pembayaran berhasil. Tiket demo kamu sudah terbit.');
    }
}
