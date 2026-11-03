<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'event_id',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'used_count',
        'start_time',
        'end_time',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'event_id' => 'integer',
            'value' => 'integer',
            'min_order_amount' => 'integer',
            'max_discount_amount' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isApplicable(int $subtotal, ?string &$reason = null, ?int $eventId = null): bool
    {
        if (! $this->active) {
            $reason = 'Kode diskon tidak aktif.';
            return false;
        }

        if ($this->event_id !== null) {
            if ($eventId === null || (int) $eventId !== (int) $this->event_id) {
                $eventTitle = $this->event?->title ?? 'acara tertentu';
                $reason = "Kode diskon ini khusus berlaku untuk event \"{$eventTitle}\".";
                return false;
            }
        }

        if ($this->start_time && now()->lt($this->start_time)) {
            $reason = 'Kode diskon belum dapat digunakan.';
            return false;
        }

        if ($this->end_time && now()->gt($this->end_time)) {
            $reason = 'Kode diskon sudah kedaluwarsa.';
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            $reason = 'Kuota penggunaan kode diskon sudah habis.';
            return false;
        }

        if ($subtotal < $this->min_order_amount) {
            $reason = 'Minimal pembelian untuk kode ini adalah Rp' . number_format($this->min_order_amount, 0, ',', '.') . '.';
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $subtotal): int
    {
        if ($this->type === 'percent') {
            $amount = (int) round(($subtotal * $this->value) / 100);
            if ($this->max_discount_amount !== null && $amount > $this->max_discount_amount) {
                $amount = $this->max_discount_amount;
            }
            return min($amount, $subtotal);
        }

        // Fixed rupiah discount
        return min((int) $this->value, $subtotal);
    }
}
