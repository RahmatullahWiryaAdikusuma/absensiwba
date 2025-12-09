<?php
namespace App\Listeners;

use App\Events\LeaveRequested;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class SendLeaveRequestNotification
{
    public function handle(LeaveRequested $event)
    { 
        $recipients = User::role('super_admin')->get();

        Notification::make()
            ->title('Pengajuan Cuti Baru')
            ->body("Karyawan {$event->leave->user->name} mengajukan cuti.")
            ->warning()
            ->actions([
                Action::make('review')
                    ->button()
                    ->url("/admin/leaves/{$event->leave->id}/edit"),  
            ])
            ->sendToDatabase($recipients);
    }
}