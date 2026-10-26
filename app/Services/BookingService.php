<?php

namespace App\Services;

use App\Models\{Event, Order, TicketCategory, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function checkout(User $user, Event $event, array $quantities, string $key): Order
    {
        return DB::transaction(function () use ($user, $event, $quantities, $key) {
            // Serialize this customer's repeated submissions; the unique key is a second safeguard.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($existing = Order::where('user_id', $user->id)->where('checkout_key', $key)->first()) {
                return $existing;
            }
            $event->refresh();
            if ($event->trashed() || $event->status !== 'published' || $event->start_time->isPast()) {
                throw ValidationException::withMessages(['tickets' => 'Penjualan untuk event ini sudah ditutup.']);
            }
            $selected = collect($quantities)->filter(fn ($quantity) => (int) $quantity > 0);
            if ($selected->isEmpty() || $selected->sum() > 20) {
                throw ValidationException::withMessages(['tickets' => 'Pilih 1 sampai 20 tiket per pesanan.']);
            }
            $types = TicketCategory::where('event_id', $event->id)->whereIn('id', $selected->keys())->orderBy('id')->lockForUpdate()->get();
            if ($types->count() !== $selected->count()) {
                throw ValidationException::withMessages(['tickets' => 'Pilihan tiket tidak sesuai dengan event ini.']);
            }
            $total = 0;
            foreach ($types as $type) {
                $quantity = (int) $selected[$type->id];
                if (!$type->active || $quantity > $type->stock || $quantity > $type->max_per_order) {
                    throw ValidationException::withMessages(['tickets' => "Tiket {$type->name} tidak tersedia dalam jumlah tersebut. Silakan periksa stok terbaru."]);
                }
                $total += $type->price * $quantity;
            }
            $order = Order::create(['number' => (string) Str::uuid(), 'user_id' => $user->id, 'checkout_key' => $key, 'status' => 'pending', 'total' => $total, 'expires_at' => now()->addMinutes(config('ticketing.reservation_minutes'))]);
            foreach ($types as $type) {
                $quantity = (int) $selected[$type->id];
                $type->decrement('stock', $quantity);
                $order->items()->create(['ticket_category_id' => $type->id, 'event_title' => $event->title, 'ticket_name' => $type->name, 'event_start_time' => $event->start_time, 'venue' => $event->venue.', '.$event->city, 'quantity' => $quantity, 'unit_price' => $type->price, 'subtotal' => $type->price * $quantity]);
            }
            return $order;
        }, 3);
    }

    public function release(Order $order, string $status = 'cancelled'): void
    {
        DB::transaction(function () use ($order, $status) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') { return; }
            if ($status === 'expired' && $locked->expires_at->isFuture()) { return; }
            // Match checkout's inventory lock order to reduce deadlocks.
            foreach ($locked->items()->orderBy('ticket_category_id')->get() as $item) {
                $type = TicketCategory::whereKey($item->ticket_category_id)->lockForUpdate()->firstOrFail();
                $type->increment('stock', $item->quantity);
            }
            $locked->update(['status' => $status]);
        }, 3);
    }

    public function payDemo(Order $order): void
    {
        $expired = DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') { return false; }
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Pesanan ini sudah tidak dapat dibayar.']);
            }
            if ($locked->expires_at->isPast()) {
                $this->release($locked, 'expired');
                return true; // Commit the released stock before reporting the expired order.
            }
            $locked->update(['status' => 'paid', 'paid_at' => now()]);
            foreach ($locked->items as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $item->tickets()->create(['code' => (string) Str::uuid(), 'status' => 'valid']);
                }
            }
            return false;
        }, 3);
        if ($expired) { throw ValidationException::withMessages(['order' => 'Waktu pembayaran habis. Stok tiket telah dikembalikan.']); }
    }

    public function expire(): int
    {
        $count = 0;
        Order::where('status', 'pending')->where('expires_at', '<=', now())->chunkById(100, function ($orders) use (&$count) {
            foreach ($orders as $order) { $this->release($order, 'expired'); $count++; }
        });
        return $count;
    }
}
