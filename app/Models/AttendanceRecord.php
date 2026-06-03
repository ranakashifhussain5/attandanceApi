<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'attendance_session_id', 'student_id', 'status',
        'wifi_bssid_captured', 'latitude_captured', 'longitude_captured',
        'wifi_verified', 'gps_verified', 'ip_address', 'rejection_reason', 'marked_at',
    ];

    protected $casts = [
        'wifi_verified' => 'boolean',
        'gps_verified'  => 'boolean',
        'marked_at'     => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
}
