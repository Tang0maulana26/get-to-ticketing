<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One gallery image of an event, ordered by position.
 */
class EventImage extends Model
{
    protected $fillable = ['path', 'position'];
}
