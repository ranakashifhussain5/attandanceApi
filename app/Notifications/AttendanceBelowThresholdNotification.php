<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceBelowThresholdNotification extends Notification
{
    use Queueable;

    public function __construct(private array $stats) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'below_threshold',
            'percentage' => $this->stats['percentage'],
            'attended'   => $this->stats['attended'],
            'total'      => $this->stats['total_sessions'],
            'message'    => "Warning: Your attendance has dropped to {$this->stats['percentage']}%. Minimum required is 75%.",
        ];
    }
}
