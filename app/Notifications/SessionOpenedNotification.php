<?php

namespace App\Notifications;

use App\Models\AttendanceSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionOpenedNotification extends Notification
{
    use Queueable;

    public function __construct(private AttendanceSession $session) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'session_opened',
            'session_id' => $this->session->id,
            'course'     => $this->session->schedule->batchCourse->course->name,
            'room'       => $this->session->schedule->room->name,
            'opened_at'  => $this->session->opened_at->toTimeString(),
            'message'    => 'Attendance session is now open for '
                          . $this->session->schedule->batchCourse->course->name,
        ];
    }
}
