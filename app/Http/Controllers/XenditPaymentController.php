<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BookingService;
use App\Services\XenditService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Controller to manage Xendit Sandbox / Production checkout redirection, status synchronization, and webhooks.
 */
class XenditPaymentController extends Controller
{
    public function __construct(
        private XenditService $xendit,
        private BookingService $bookings,
    ) {}

    /**
     * Redirect customer to Xendit Invoice checkout page.
     */
    public function checkout(Order $order): RedirectResponse
    {
        Gate::authorize('update', $order);

        if ($order->status !== 'pending') {
            return to_route('orders.show', $order)->with('notice', __('This order is no longer pending payment.'));
        }

        if ($order->expires_at->isPast()) {
            $this->bookings->release($order, 'expired');
            return to_route('orders.show', $order)->with('error', __('Payment deadline for this order has expired.'));
        }

        if ($order->total <= 0) {
            $this->bookings->markAsPaid($order, 'discount', 'FREE_PROMO');
            return to_route('orders.show', $order)->with('success', __('Order is fully covered by discount! Your tickets are now active.'));
        }

        try {
            // If already generated invoice is still valid, reuse or generate new
            if (empty($order->xendit_invoice_url)) {
                $invoice = $this->xendit->createInvoice($order);

                $order->update([
                    'xendit_invoice_id' => $invoice['id'],
                    'xendit_invoice_url' => $invoice['invoice_url'],
                    'payment_method' => 'xendit',
                ]);
            }

            return redirect()->away($order->xendit_invoice_url);
        } catch (Exception $e) {
            Log::error('Xendit Checkout Error: '.$e->getMessage(), ['order' => $order->number]);

            return to_route('orders.show', $order)->with('error', __('Failed connecting to Xendit: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Manually check / sync invoice status directly from Xendit.
     * Useful for local testing where webhooks cannot hit localhost without a public tunnel.
     */
    public function check(Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);

        if ($order->status === 'paid') {
            return to_route('orders.show', $order)->with('success', __('Order is already paid and your tickets have been issued.'));
        }

        if (empty($order->xendit_invoice_id)) {
            return to_route('orders.show', $order)->with('notice', __('No Xendit invoice has been created for this order yet.'));
        }

        try {
            $invoice = $this->xendit->getInvoice($order->xendit_invoice_id);
            $status = strtoupper((string) ($invoice['status'] ?? ''));

            if ($status === 'PAID' || $status === 'SETTLED') {
                $channel = $invoice['payment_channel'] ?? $invoice['payment_method'] ?? 'XENDIT';
                $this->bookings->markAsPaid($order, 'xendit', $channel, ignoreExpiry: true);

                return to_route('orders.show', $order)->with('success', __('Xendit payment confirmed! Your tickets are now active.'));
            }

            if ($status === 'EXPIRED') {
                $this->bookings->release($order, 'expired', forceExpire: true);

                return to_route('orders.show', $order)->with('error', __('Xendit payment invoice has expired.'));
            }

            return to_route('orders.show', $order)->with('notice', __('Payment status at Xendit is still PENDING. Please complete payment on the Xendit simulator.'));
        } catch (Exception $e) {
            return to_route('orders.show', $order)->with('error', __('Failed checking status at Xendit: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Webhook notification handler from Xendit when an invoice is paid or updated.
     */
    public function webhook(Request $request): JsonResponse
    {
        $expectedToken = config('xendit.webhook_token');
        if (! empty($expectedToken)) {
            $callbackToken = $request->header('x-callback-token');
            if ($callbackToken !== $expectedToken) {
                Log::warning('Xendit Webhook Unauthorized token mismatch', ['token' => $callbackToken]);
                return response()->json(['message' => 'Unauthorized token'], 401);
            }
        }

        $status = strtoupper((string) $request->input('status', ''));
        $externalId = $request->input('external_id');

        Log::info('Xendit Webhook Received', [
            'status' => $status,
            'external_id' => $externalId,
            'id' => $request->input('id'),
        ]);

        if (empty($externalId)) {
            return response()->json(['message' => 'Missing external_id'], 400);
        }

        $order = Order::where('number', $externalId)->first();
        if (! $order) {
            Log::warning('Xendit Webhook Order Not Found', ['external_id' => $externalId]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            if ($status === 'PAID' || $status === 'SETTLED') {
                $channel = $request->input('payment_channel') ?? $request->input('bank_code') ?? $request->input('payment_method') ?? 'XENDIT';
                $this->bookings->markAsPaid($order, 'xendit', $channel, ignoreExpiry: true);
            } elseif ($status === 'EXPIRED') {
                $this->bookings->release($order, 'expired', forceExpire: true);
            }
        } catch (\Throwable $e) {
            Log::warning('Xendit Webhook ProcessingSkipped: '.$e->getMessage(), [
                'order' => $order->number,
                'status' => $status,
            ]);
        }

        return response()->json(['status' => 'OK']);
    }
}
