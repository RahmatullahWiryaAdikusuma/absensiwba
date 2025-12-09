<?php
namespace App\Listeners;

use App\Events\AttendanceRecorded;
use App\Models\User;
use Filament\Notifications\Notification;

class SendAttendanceNotification
{
    public function handle(AttendanceRecorded $event)
    {
        $recipients = User::role('super_admin')->get();
        $user = $event->attendance->user;
        $time = now()->format('H:i');
        
        $title = $event->type === 'check_in' ? 'Karyawan Hadir' : 'Karyawan Pulang';
        $body = $event->type === 'check_in' 
            ? "{$user->name} absen masuk pada {$time}."
            : "{$user->name} absen pulang pada {$time}.";

        Notification::make()
            ->title($title)
            ->body($body)
            ->icon($event->type === 'check_in' ? 'heroicon-o-arrow-right-end-on-rectangle' : 'heroicon-o-arrow-left-start-on-rectangle')
            ->sendToDatabase($recipients);
    }
}