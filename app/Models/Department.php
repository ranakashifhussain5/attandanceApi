<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'description'];

    public function programs(): HasMany { return $this->hasMany(Program::class); }
    public function hods(): HasMany     { return $this->hasMany(User::class)->where('role', 'hod'); }
    public function rooms(): HasMany    { return $this->hasMany(Room::class); }
}
