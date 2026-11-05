<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Event;
use App\Models\Order;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Core booking rules: checkout, stock reservation, release/expiry and demo payment.
 * Every mutation runs in a DB transaction with row locks (always in the same order) to stay safe under concurrent requests.
 * Do not move stock logic to the frontend or remove these locks.
 */
class BookingService
{
    private const MAX_TICKETS_PER_ORDER = 20;

    public function checkout(User $user, Event $event, array $quantities, string $key, ?string $discountCode = null): Order
    {
        // Free stock held by this event's expired reservations before checking availability.
        $this->expire(event: $event);

        return DB::transaction(function () use ($user, $event, $quantities, $key, $discountCode) {
            // Serialize this customer's repeated submissions; the unique key is a second safeguard.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existing = Order::where('user_id', $user->id)->where('checkout_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            $event->refresh();
            if (! $this->isOnSale($event)) {
                $this->fail('tickets', 'Penjualan untuk event ini sudah ditutup.');
            }

            $selected = collect($quantities)->map(fn ($quantity) => (int) $quantity)->filter(fn ($quantity) => $quantity > 0);
            if ($selected->isEmpty() || $selected->sum() > self::MAX_TICKETS_PER_ORDER) {
                $this->fail('tickets', 'Pilih 1 sampai '.self::MAX_TICKETS_PER_ORDER.' tiket per pesanan.');
            }

            $types = TicketCategory::where('event_id', $event->id)
                ->whereIn('id', $selected->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($types->count() !== $selected->count()) {
                $this->fail('tickets', 'Pilihan tiket tidak sesuai dengan event ini.');
            }

            foreach ($types as $type) {
                $quantity = $selected[$type->id];
                if (! $type->active || $quantity > $type->stock || $quantity > $type->max_per_order) {
                    $this->fail('tickets', "Tiket {$type->name} tidak tersedia dalam jumlah tersebut. Silakan periksa stok terbaru.");
                }
            }

            $subtotal = (int) $types->sum(fn ($type) => $type->price * $selected[$type->id]);
            $discount = null;
            $discountAmount = 0;

            $cleanCode = strtoupper(trim((string) $discountCode));
            if ($cleanCode !== '') {
                $discount = Discount::where('code', $cleanCode)->lockForUpdate()->first();
                if (! $discount) {
                    $this->fail('discount_code', __('Discount code not found.'));
                }
                $reason = null;
                if (! $discount->isApplicable($subtotal, $reason, $event->id)) {
                    $this->fail('discount_code', $reason ?? __('Discount code cannot be applied.'));
                }
                $discountAmount = $discount->calculateDiscount($subtotal);
                $discount->increment('used_count');
            }

            $total = max(0, $subtotal - $discountAmount);

            $order = Order::create([
                'number' => (string) Str::uuid(),
                'user_id' => $user->id,
                'checkout_key' => $key,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount_id' => $discount?->id,
                'discount_code' => $discount?->code,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'expires_at' => now()->addMinutes(config('ticketing.reservation_minutes')),
            ]);

            foreach ($types as $type) {
                $quantity = $selected[$type->id];
                $type->decrement('stock', $quantity);
                // Snapshot names and prices so later admin edits never rewrite order history.
                $order->items()->create([
                    'ticket_category_id' => $type->id,
                    'event_title' => $event->title,
                    'ticket_name' => $type->name,
                    'event_start_time' => $event->start_time,
                    'venue' => $event->venue.', '.$event->city,
                    'quantity' => $quantity,
                    'unit_price' => $type->price,
                    'subtotal' => $type->price * $quantity,
                ]);
            }

            return $order;
        }, 3);
    }

    public function release(Order $order, string $status = 'cancelled', bool $forceExpire = false): void
    {
        DB::transaction(function () use ($order, $status, $forceExpire) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                return;
            }
            if ($status === 'expired' && ! $forceExpire && $locked->expires_at->isFuture()) {
                return;
            }

            // Match checkout's inventory lock order to reduce deadlocks.
            foreach ($locked->items()->orderBy('ticket_category_id')->get() as $item) {
                TicketCategory::whereKey($item->ticket_category_id)->lockForUpdate()->firstOrFail()->increment('stock', $item->quantity);
            }

            // Release discount usage count if used
            if ($locked->discount_id) {
                $discount = Discount::whereKey($locked->discount_id)->lockForUpdate()->first();
                if ($discount && $discount->used_count > 0) {
                    $discount->decrement('used_count');
                }
            }

            $attributes = ['status' => $status];
            if ($status === 'expired' && $forceExpire && $locked->expires_at->isFuture()) {
                $attributes['expires_at'] = now()->subSecond();
            }

            $locked->update($attributes);
        }, 3);
    }

    public function payDemo(Order $order): void
    {
        $this->markAsPaid($order, 'demo', 'DEMO_SIMULATION');
    }

    /**
     * Mark an order as paid, issue tickets, and record payment information securely.
     */
    public function markAsPaid(Order $order, string $paymentMethod = 'demo', ?string $paymentChannel = null, bool $ignoreExpiry = false): void
    {
        // The transaction returns an error message instead of throwing, so released stock is committed first.
        $error = DB::transaction(function () use ($order, $paymentMethod, $paymentChannel, $ignoreExpiry) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return null;
            }
            if ($locked->status !== 'pending') {
                return 'Pesanan ini sudah tidak dapat dibayar.';
            }
            if (! $ignoreExpiry && $locked->expires_at->isPast()) {
                $this->release($locked, 'expired');

                return 'Waktu pembayaran habis. Stok tiket telah dikembalikan.';
            }

            $locked->load('items.ticketCategory.event');
            if ($locked->items->contains(fn ($item) => ! $this->isOnSale($item->ticketCategory->event))) {
                $this->release($locked);

                return 'Event ini sudah tidak tersedia. Pesanan dibatalkan dan stok tiket dikembalikan.';
            }

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $paymentMethod,
                'payment_channel' => $paymentChannel,
            ]);

            foreach ($locked->items as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $item->tickets()->create(['code' => (string) Str::uuid(), 'status' => 'valid']);
                }
            }

            return null;
        }, 3);

        if ($error) {
            $this->fail('order', $error);
        }

        // Send payment confirmation and e-ticket email with attached PDF (failsafe)
        try {
            $order->refresh()->loadMissing(['items.tickets', 'user']);
            $recipientEmail = $order->customer_email ?? $order->user?->email;
            if ($recipientEmail) {
                \Illuminate\Support\Facades\Mail::to($recipientEmail)->send(new \App\Mail\OrderPaidMail($order));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Release expired reservations, optionally limited to one event or one customer.
     */
    public function expire(?Event $event = null, ?User $user = null): int
    {
        $count = 0;
        Order::where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->when($event, fn ($query) => $query->whereHas('items.ticketCategory', fn ($q) => $q->where('event_id', $event->id)))
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    $this->release($order, 'expired');
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Retrieve gate scanner configuration (system on/off & start_time window lock) for an event.
     *
     * @return array{system_active: bool, time_lock_enabled: bool, hours_before_start: int}
     */
    public function getGateConfig(?int $eventId = null): array
    {
        $key = 'gate_config_' . ($eventId ?: 'global');
        $stored = \Illuminate\Support\Facades\Cache::get($key);

        if (is_array($stored)) {
            return [
                'system_active' => (bool) ($stored['system_active'] ?? true),
                'time_lock_enabled' => (bool) ($stored['time_lock_enabled'] ?? false),
                'hours_before_start' => max(0, (int) ($stored['hours_before_start'] ?? 24)),
            ];
        }

        return [
            'system_active' => true,
            'time_lock_enabled' => false,
            'hours_before_start' => 24,
        ];
    }

    /**
     * Save gate scanner configuration (system on/off & start_time window lock) for an event.
     *
     * @return array{system_active: bool, time_lock_enabled: bool, hours_before_start: int}
     */
    public function setGateConfig(?int $eventId, bool $systemActive, bool $timeLockEnabled, int $hoursBeforeStart = 24): array
    {
        $config = [
            'system_active' => $systemActive,
            'time_lock_enabled' => $timeLockEnabled,
            'hours_before_start' => max(0, $hoursBeforeStart),
        ];

        $key = 'gate_config_' . ($eventId ?: 'global');
        \Illuminate\Support\Facades\Cache::forever($key, $config);

        return $config;
    }

    /**
     * Process admission check-in for a ticket code.
     * Validates HMAC signature if attached, ensures the order is paid, checks gate lock & start_time window,
     * and guards against double-entry.
     *
     * @return array{ok: bool, message: string, ticket?: \App\Models\Ticket, status: string}
     */
    public function checkIn(string $rawInput, ?int $expectedEventId = null): array
    {
        $raw = trim($rawInput);
        $parts = explode('.', $raw);
        $code = trim($parts[0]);
        $signature = isset($parts[1]) ? trim($parts[1]) : null;

        // Guard against PostgreSQL UUID syntax errors (SQLSTATE[22P02]) on invalid input/typos
        if (! Str::isUuid($code)) {
            return [
                'ok' => false,
                'status' => 'not_found',
                'message' => __('Invalid ticket code format or ticket is not registered in the system.'),
            ];
        }

        // If a signature is provided, verify it immediately
        if ($signature !== null && ! \App\Models\Ticket::verifySignature($code, $signature)) {
            return [
                'ok' => false,
                'status' => 'forged',
                'message' => __('WARNING: Digital cryptographic signature mismatch! Ticket may be forged or tampered.'),
            ];
        }

        return DB::transaction(function () use ($code, $expectedEventId) {
            /** @var \App\Models\Ticket|null $ticket */
            $ticket = \App\Models\Ticket::where('code', $code)
                ->with(['orderItem.order.user', 'orderItem.ticketCategory.event'])
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                return [
                    'ok' => false,
                    'status' => 'not_found',
                    'message' => __('Ticket code was not found in the database.'),
                ];
            }

            $order = $ticket->orderItem?->order;
            if (! $order || $order->status !== 'paid') {
                return [
                    'ok' => false,
                    'status' => 'unpaid',
                    'message' => __('This ticket belongs to an unpaid or expired order.'),
                ];
            }

            $event = $ticket->orderItem?->ticketCategory?->event;
            if ($expectedEventId !== null && (int) $event?->id !== (int) $expectedEventId) {
                return [
                    'ok' => false,
                    'status' => 'wrong_event',
                    'message' => __('This ticket is registered for a different concert event.'),
                    'ticket' => $ticket,
                ];
            }

            // Check Gate System Active & Start-Time Window Lock
            $gateConfig = $this->getGateConfig($event?->id ?? $expectedEventId);

            if (! $gateConfig['system_active']) {
                return [
                    'ok' => false,
                    'status' => 'gate_closed',
                    'message' => __('GATE LOCKED: Ticket scanning for this event is currently disabled by Admin.'),
                    'ticket' => $ticket,
                ];
            }

            $startTime = $event?->start_time ?? $ticket->orderItem?->event_start_time;

            if ($gateConfig['time_lock_enabled'] && $startTime) {
                $hoursBefore = (int) $gateConfig['hours_before_start'];
                $openGateTime = $startTime->copy()->subHours($hoursBefore);

                if (now()->lt($openGateTime)) {
                    $openStr = $openGateTime->format('d M Y H:i') . ' WIB';
                    $startStr = $startTime->format('d M Y H:i') . ' WIB';
                    return [
                        'ok' => false,
                        'status' => 'too_early',
                        'message' => __('NOT OPEN YET: Gate opens at :open (:hours hours before event start :start).', [
                            'open' => $openStr,
                            'hours' => $hoursBefore,
                            'start' => $startStr,
                        ]),
                        'ticket' => $ticket,
                    ];
                }
            }

            if ($ticket->status === 'used' || $ticket->checked_in_at !== null) {
                $time = $ticket->checked_in_at ? $ticket->checked_in_at->format('H:i:s') . ' WIB' : __('earlier');
                return [
                    'ok' => false,
                    'status' => 'already_used',
                    'message' => __('WARNING: This ticket was already used for check-in at :time!', ['time' => $time]),
                    'ticket' => $ticket,
                ];
            }

            // Mark ticket as checked-in
            $ticket->update([
                'status' => 'used',
                'checked_in_at' => now(),
            ]);

            return [
                'ok' => true,
                'status' => 'valid',
                'message' => __('Check-in successful! Ticket is valid.'),
                'ticket' => $ticket,
            ];
        }, 3);
    }

    private function isOnSale(?Event $event): bool
    {
        return $event && ! $event->trashed() && $event->status === 'published' && $event->start_time->isFuture();
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
