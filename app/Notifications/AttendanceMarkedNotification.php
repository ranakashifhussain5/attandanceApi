<?php

namespace App\Notifications;

use App\Models\{AttendanceRecord, AttendanceSession};
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceMarkedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private AttendanceSession $session,
        private AttendanceRecord  $record
    ) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'attendance_marked',
            'session_id' => $this->session->id,
            'marked_at'  => $this->record->marked_at->toDateTimeString(),
            'message'    => 'Your attendance has been recorded for '
                          . $this->session->schedule->batchCourse->course->name,
        ];
    }
}
