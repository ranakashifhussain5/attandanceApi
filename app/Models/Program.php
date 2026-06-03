<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Program extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id', 'name', 'code', 'duration_years', 'description', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function courses(): HasMany      { return $this->hasMany(Course::class); }
    public function batches(): HasMany      { return $this->hasMany(Batch::class); }
}
