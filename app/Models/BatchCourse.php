<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class BatchCourse extends Model
{
    protected $fillable = ['batch_id', 'course_id', 'teacher_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function batch(): BelongsTo   { return $this->belongsTo(Batch::class); }
    public function course(): BelongsTo  { return $this->belongsTo(Course::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function schedules(): HasMany { return $this->hasMany(Schedule::class); }
}
