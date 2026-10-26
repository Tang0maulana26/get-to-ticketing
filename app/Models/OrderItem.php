<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = ['ticket_category_id', 'event_title', 'ticket_name', 'event_start_time', 'venue', 'quantity', 'unit_price', 'subtotal'];
    protected function casts(): array { return ['event_start_time' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function ticketCategory(): BelongsTo { return $this->belongsTo(TicketCategory::class); }
    public function tickets(): HasMany { return $this->hasMany(Ticket::class); }
}
