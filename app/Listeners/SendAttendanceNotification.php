<?php
namespace App\Listeners;

use App\Events\AttendanceRecorded;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Filament\Resources\AttendanceResource;

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
            ->actions([
                Action::make('view')
                    ->label('Lihat')
                    ->button()
                    ->markAsRead() 
                    ->url(AttendanceResource::getUrl('edit', ['record' => $event->attendance])),
                
                Action::make('mark_read')
                    ->label('Tandai Terbaca')
                    ->link()
                    ->markAsRead(), 
            ])
            ->sendToDatabase($recipients);
    }
}