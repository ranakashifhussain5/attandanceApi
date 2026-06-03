<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableGenerationLog extends Model
{
    protected $fillable = [
        'hod_id', 'department_id', 'semester', 'status',
        'schedules_created', 'conflicts_detected', 'conflict_details',
        'error_message', 'generated_at',
    ];

    protected $casts = [
        'conflict_details' => 'array',
        'generated_at'     => 'datetime',
    ];

    public function hod(): BelongsTo        { return $this->belongsTo(User::class, 'hod_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
}
