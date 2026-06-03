<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class AttendanceSession extends Model
{
    protected $fillable = [
        'schedule_id', 'teacher_id', 'session_date',
        'opened_at', 'closed_at', 'status', 'total_present', 'total_students',
    ];

    protected $casts = [
        'opened_at'    => 'datetime',
        'closed_at'    => 'datetime',
        'session_date' => 'date',
    ];

    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
    public function teacher(): BelongsTo  { return $this->belongsTo(User::class, 'teacher_id'); }
    public function records(): HasMany    { return $this->hasMany(AttendanceRecord::class); }

    public function isOpen(): bool { return $this->status === 'open'; }

    public function recalculateTotals(): void
    {
        $this->total_present  = $this->records()->where('status', 'present')->count();
        $this->total_students = $this->records()->count();
        $this->save();
    }
}
