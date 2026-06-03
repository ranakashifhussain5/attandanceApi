<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Schedule extends Model
{
    protected $fillable = [
        'batch_course_id', 'room_id', 'time_slot_id',
        'day_of_week', 'effective_from', 'effective_to', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function batchCourse(): BelongsTo { return $this->belongsTo(BatchCourse::class); }
    public function room(): BelongsTo        { return $this->belongsTo(Room::class); }
    public function timeSlot(): BelongsTo    { return $this->belongsTo(TimeSlot::class); }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function isWithinClassWindow(): bool
    {
        $now = now();
        if ($now->dayOfWeekIso !== $this->day_of_week) return false;

        $start = $now->copy()->setTimeFromTimeString($this->timeSlot->start_time)->subMinutes(5);
        $end   = $now->copy()->setTimeFromTimeString($this->timeSlot->end_time)->addMinutes(5);

        return $now->between($start, $end);
    }
}
