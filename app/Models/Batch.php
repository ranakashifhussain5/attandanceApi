<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class Batch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'program_id', 'name', 'semester', 'start_year', 'end_year', 'max_students', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function program(): BelongsTo { return $this->belongsTo(Program::class); }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'batch_students', 'batch_id', 'user_id')
                    ->withPivot('enrolled_at', 'status')
                    ->withTimestamps();
    }

    public function batchStudents(): HasMany { return $this->hasMany(BatchStudent::class); }
    public function batchCourses(): HasMany  { return $this->hasMany(BatchCourse::class); }
}
