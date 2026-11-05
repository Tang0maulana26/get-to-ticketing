<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Event;
use App\Models\Order;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Customer order flow: checkout, order history/detail, cancellation and the local demo payment.
 * Stock rules live in BookingService; this controller only authorizes and redirects.
 */
class OrderController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request)
    {
        // Only this customer's reservations; the scheduler handles everyone else.
        $this->bookings->expire(user: $request->user());

        $tab = in_array($request->query('tab'), ['all', 'active', 'past'], true)
            ? $request->query('tab')
            : 'all';

        $cutoff = now()->startOfDay();

        $activeFilter = function ($query) use ($cutoff) {
            $query->whereIn('status', ['paid', 'pending'])
                ->whereHas('items', function ($q) use ($cutoff) {
                    $q->whereNull('event_start_time')
                        ->orWhere('event_start_time', '>=', $cutoff);
                });
        };

        $pastFilter = function ($query) use ($cutoff) {
            $query->where(function ($q) use ($cutoff) {
                $q->whereIn('status', ['cancelled', 'expired'])
                    ->orWhereDoesntHave('items', function ($itemQuery) use ($cutoff) {
                        $itemQuery->whereNull('event_start_time')
                            ->orWhere('event_start_time', '>=', $cutoff);
                    });
            });
        };

        $baseQuery = $request->user()->orders();
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->tap($activeFilter)->count(),
            'past' => (clone $baseQuery)->tap($pastFilter)->count(),
        ];

        $ordersQuery = $request->user()->orders()->with('items');
        if ($tab === 'active') {
            $activeFilter($ordersQuery);
        } elseif ($tab === 'past') {
            $pastFilter($ordersQuery);
        }

        return view('orders.index', [
            'orders' => $ordersQuery->latest()->paginate(10)->withQueryString(),
            'tab' => $tab,
            'counts' => $counts,
        ]);
    }

    public function store(CheckoutRequest $request, Event $event)
    {
        $order = $this->bookings->checkout(
            $request->user(),
            $event,
            $request->validated('tickets'),
            $request->validated('checkout_key'),
            $request->validated('discount_code'),
        );

        return to_route('orders.show', $order)
            ->with('success', __('Tickets reserved temporarily. Complete payment within :minutes minutes.', ['minutes' => config('ticketing.reservation_minutes')]));
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

        return to_route('orders.show', $order)->with('success', 'Pembayaran berhasil dikonfirmasi. E-Ticket kamu sudah terbit.');
    }
}
