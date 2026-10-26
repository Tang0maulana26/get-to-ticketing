<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = ['category_id', 'title', 'slug', 'summary', 'description', 'banner', 'venue', 'city', 'address', 'start_time', 'end_time', 'status', 'featured'];

    protected function casts(): array { return ['start_time' => 'datetime', 'end_time' => 'datetime', 'featured' => 'boolean']; }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function ticketCategories(): HasMany { return $this->hasMany(TicketCategory::class); }
    public function images(): HasMany { return $this->hasMany(EventImage::class)->orderBy('position')->orderBy('id'); }
    public function getBannerUrlAttribute(): string
    {
        if (!$this->banner) { return asset('images/poster-blue.svg'); }
        return str_starts_with($this->banner, 'images/') ? asset($this->banner) : Storage::disk('public')->url($this->banner);
    }
}
