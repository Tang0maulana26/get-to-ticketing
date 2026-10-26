<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['number', 'user_id', 'checkout_key', 'status', 'currency', 'total', 'expires_at', 'paid_at'];
    protected function casts(): array { return ['expires_at' => 'datetime', 'paid_at' => 'datetime', 'total' => 'integer']; }
    public function getRouteKeyName(): string { return 'number'; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
}
