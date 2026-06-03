<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    protected $fillable = ['label', 'start_time', 'end_time', 'duration_minutes', 'is_active'];

    public function schedules(): HasMany { return $this->hasMany(Schedule::class); }
}
