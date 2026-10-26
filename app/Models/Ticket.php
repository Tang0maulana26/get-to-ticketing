<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = ['code', 'status', 'checked_in_at'];
    protected function casts(): array { return ['checked_in_at' => 'datetime']; }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
