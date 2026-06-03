<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'department_id', 'phone', 'profile_photo', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
    ];

    public function scopeHods($q)     { return $q->where('role', 'hod'); }
    public function scopeTeachers($q) { return $q->where('role', 'teacher'); }
    public function scopeStudents($q) { return $q->where('role', 'student'); }

    public function isHod(): bool     { return $this->role === 'hod'; }
    public function isTeacher(): bool { return $this->role === 'teacher'; }
    public function isStudent(): bool { return $this->role === 'student'; }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedBatchCourses(): HasMany
    {
        return $this->hasMany(BatchCourse::class, 'teacher_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class, 'teacher_id');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'teacher_id');
    }

    public function batchStudents(): HasMany
    {
        return $this->hasMany(BatchStudent::class);
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'batch_students', 'user_id', 'batch_id')
                    ->withPivot('enrolled_at', 'status')
                    ->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function attendancePercentage(int $batchCourseId): float
    {
        $sessions = AttendanceSession::whereHas('schedule', fn($q) =>
            $q->where('batch_course_id', $batchCourseId)
        )->where('status', 'closed')->pluck('id');

        if ($sessions->isEmpty()) return 0.0;

        $attended = $this->attendanceRecords()
            ->whereIn('attendance_session_id', $sessions)
            ->where('status', 'present')
            ->count();

        return round(($attended / $sessions->count()) * 100, 2);
    }
}
