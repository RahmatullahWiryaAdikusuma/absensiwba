<?php
namespace App\Listeners;

use App\Events\LeaveStatusUpdated;
use Filament\Notifications\Notification;

class SendLeaveStatusNotification
{
    public function handle(LeaveStatusUpdated $event)
    {
        $recipient = $event->leave->user;
        $status = $event->leave->status;
        
        $notif = Notification::make()
            ->title('Status Cuti Diperbarui')
            ->body("Pengajuan cuti Anda telah diubah menjadi: " . ucfirst($status));

        if ($status === 'approved') {
            $notif->success();
        } elseif ($status === 'rejected') {
            $notif->danger();
        }

        $notif->sendToDatabase($recipient);
    }
}