<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    private const MAX_TICKETS_PER_ORDER = 20;

    public function checkout(User $user, Event $event, array $quantities, string $key): Order
    {
        // Free stock held by this event's expired reservations before checking availability.
        $this->expire(event: $event);

        return DB::transaction(function () use ($user, $event, $quantities, $key) {
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

            $order = Order::create([
                'number' => (string) Str::uuid(),
                'user_id' => $user->id,
                'checkout_key' => $key,
                'status' => 'pending',
                'total' => $types->sum(fn ($type) => $type->price * $selected[$type->id]),
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

    public function release(Order $order, string $status = 'cancelled'): void
    {
        DB::transaction(function () use ($order, $status) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                return;
            }
            if ($status === 'expired' && $locked->expires_at->isFuture()) {
                return;
            }

            // Match checkout's inventory lock order to reduce deadlocks.
            foreach ($locked->items()->orderBy('ticket_category_id')->get() as $item) {
                TicketCategory::whereKey($item->ticket_category_id)->lockForUpdate()->firstOrFail()->increment('stock', $item->quantity);
            }
            $locked->update(['status' => $status]);
        }, 3);
    }

    public function payDemo(Order $order): void
    {
        // The transaction returns an error message instead of throwing, so released stock is committed first.
        $error = DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return null;
            }
            if ($locked->status !== 'pending') {
                return 'Pesanan ini sudah tidak dapat dibayar.';
            }
            if ($locked->expires_at->isPast()) {
                $this->release($locked, 'expired');

                return 'Waktu pembayaran habis. Stok tiket telah dikembalikan.';
            }

            $locked->load('items.ticketCategory.event');
            if ($locked->items->contains(fn ($item) => ! $this->isOnSale($item->ticketCategory->event))) {
                $this->release($locked);

                return 'Event ini sudah tidak tersedia. Pesanan dibatalkan dan stok tiket dikembalikan.';
            }

            $locked->update(['status' => 'paid', 'paid_at' => now()]);
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

    private function isOnSale(?Event $event): bool
    {
        return $event && ! $event->trashed() && $event->status === 'published' && $event->start_time->isFuture();
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
