<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailability extends Model
{
    protected $fillable = ['teacher_id', 'day_of_week', 'available_from', 'available_to'];

    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
}
