<?php
namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\{Event, Order};
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request, BookingService $bookings)
    {
        $bookings->expire();
        return view('orders.index', ['orders' => Order::where('user_id', $request->user()->id)->with('items')->latest()->paginate(10)]);
    }
    public function store(CheckoutRequest $request, Event $event, BookingService $bookings)
    {
        $bookings->expire();
        $order = $bookings->checkout($request->user(), $event, $request->validated('tickets'), $request->validated('checkout_key'));
        return to_route('orders.show', $order)->with('success', 'Tiket disimpan sementara. Selesaikan pembayaran dalam 15 menit.');
    }
    public function show(Order $order, BookingService $bookings)
    {
        Gate::authorize('view', $order);
        if ($order->status === 'pending' && $order->expires_at->isPast()) { $bookings->release($order, 'expired'); }
        $order->refresh()->load('items.tickets');
        return view('orders.show', compact('order'));
    }
    public function cancel(Order $order, BookingService $bookings)
    {
        Gate::authorize('update', $order); $bookings->release($order);
        return to_route('orders.show', $order)->with('success', 'Status pesanan diperbarui.');
    }
    public function pay(Order $order, BookingService $bookings)
    {
        Gate::authorize('update', $order);
        abort_unless(app()->environment('local', 'testing') && config('ticketing.demo_payments'), 403);
        $bookings->payDemo($order);
        return to_route('orders.show', $order)->with('success', 'Simulasi pembayaran berhasil. Tiket demo kamu sudah terbit.');
    }
}
