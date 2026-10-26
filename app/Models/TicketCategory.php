<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    protected $fillable = ['event_id', 'name', 'description', 'price', 'capacity', 'stock', 'max_per_order', 'active'];
    protected function casts(): array { return ['active' => 'boolean', 'price' => 'integer', 'stock' => 'integer', 'capacity' => 'integer']; }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
}
