<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Event category (e.g. Concert). Lets the catalog support other event types later without schema changes.
 */
class Category extends Model
{
    protected $fillable = ['name', 'slug'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
