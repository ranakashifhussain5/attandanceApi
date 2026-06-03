<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Room extends Model
{
    protected $fillable = [
        'department_id', 'name', 'building', 'capacity', 'type',
        'wifi_bssid', 'latitude', 'longitude', 'gps_radius_meters', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function schedules(): HasMany    { return $this->hasMany(Schedule::class); }
}
