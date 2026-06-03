<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'program_id', 'name', 'code', 'credit_hours', 'semester', 'description', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function program(): BelongsTo    { return $this->belongsTo(Program::class); }
    public function batchCourses(): HasMany { return $this->hasMany(BatchCourse::class); }
}
