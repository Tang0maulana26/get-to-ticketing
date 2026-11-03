<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer purchase with status (pending, paid, cancelled, expired), expiry time and total in whole rupiah.
 */
class Order extends Model
{
    protected $fillable = [
        'number',
        'user_id',
        'checkout_key',
        'status',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'payment_method',
        'payment_channel',
        'currency',
        'subtotal',
        'discount_id',
        'discount_code',
        'discount_amount',
        'total',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'total' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
