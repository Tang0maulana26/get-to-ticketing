<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Service to handle Xendit Checkout / Invoice API integration in Sandbox and Production.
 */
class XenditService
{
    private string $secretKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->secretKey = (string) config('xendit.secret_key', '');
        $this->apiUrl = rtrim((string) config('xendit.api_url', 'https://api.xendit.co'), '/');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->secretKey);
    }

    /**
     * Create an invoice on Xendit for the specified order.
     */
    public function createInvoice(Order $order): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Kunci rahasia Xendit belum dikonfigurasi pada environment.');
        }

        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name' => "{$item->event_title} - {$item->ticket_name}",
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->unit_price,
                'category' => 'Concert Ticket',
            ];
        }

        // If discount applied, calculate remaining duration or invoice duration
        $durationSeconds = max(300, min(86400, (int) now()->diffInSeconds($order->expires_at, false)));

        $payload = [
            'external_id' => (string) $order->number,
            'amount' => (int) $order->total,
            'description' => 'Pembayaran Tiket: '.($order->items->first()?->event_title ?? 'The Get To'),
            'payer_email' => $order->user->email,
            'customer' => [
                'given_names' => $order->user->name,
                'email' => $order->user->email,
            ],
            'customer_notification_preference' => [
                'invoice_created' => ['email'],
                'invoice_paid' => ['email'],
            ],
            'success_redirect_url' => route('orders.show', $order),
            'failure_redirect_url' => route('orders.show', $order),
            'currency' => 'IDR',
            'invoice_duration' => $durationSeconds,
            'items' => $items,
        ];

        $response = Http::withBasicAuth($this->secretKey, '')
            ->timeout(20)
            ->post("{$this->apiUrl}/v2/invoices", $payload);

        if ($response->failed()) {
            Log::error('Xendit Invoice Creation Failed', [
                'order_number' => $order->number,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            $errorMsg = $response->json('message') ?? 'Gagal membuat tagihan pembayaran Xendit. Silakan coba beberapa saat lagi.';
            throw new RuntimeException($errorMsg);
        }

        return $response->json();
    }

    /**
     * Retrieve invoice details from Xendit.
     */
    public function getInvoice(string $invoiceId): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Kunci rahasia Xendit belum dikonfigurasi pada environment.');
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->timeout(15)
            ->get("{$this->apiUrl}/v2/invoices/{$invoiceId}");

        if ($response->failed()) {
            Log::error('Xendit Invoice Fetch Failed', [
                'invoice_id' => $invoiceId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new RuntimeException('Gagal mengambil data tagihan Xendit.');
        }

        return $response->json();
    }
}

