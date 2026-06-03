<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchStudent extends Model
{
    protected $fillable = ['batch_id', 'user_id', 'enrolled_at', 'status'];

    public function batch(): BelongsTo   { return $this->belongsTo(Batch::class); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
