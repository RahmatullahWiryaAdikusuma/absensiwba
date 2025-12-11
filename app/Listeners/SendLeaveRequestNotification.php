<?php
namespace App\Listeners;

use App\Events\LeaveRequested;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Filament\Resources\LeaveResource;

class SendLeaveRequestNotification
{
    public function handle(LeaveRequested $event)
{ 
    $recipients = User::role('super_admin')->get();
    
    // DEFINISIKAN VARIABLE $user DULU!
    $user = $event->leave->user; // <--- TAMBAHKAN BARIS INI

    Notification::make()
        ->title('Pengajuan Cuti Baru')
        // Sekarang $user->name sudah bisa dibaca
        ->body("{$user->name} mengajukan cuti.") 
        ->warning()
        ->actions([
            Action::make('review')
                ->label('Tinjau')
                ->button()
                ->markAsRead()
                ->url(LeaveResource::getUrl('edit', ['record' => $event->leave])),
        ])
        ->sendToDatabase($recipients);
}
}