<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::user()->id;
        $data['status'] = 'pending';
        return $data;
    }

    // === TAMBAHAN PENTING: AFTER CREATE ===
    protected function afterCreate(): void
    {
        $leave = $this->record;
        
        // Cari semua Super Admin
        $admins = User::role('super_admin')->get();

        // Kirim Notifikasi ke Lonceng Admin
        Notification::make()
            ->title('Pengajuan Cuti Baru')
            ->body("**{$leave->user->name}** mengajukan cuti: {$leave->start_date} s/d {$leave->end_date}.")
            ->warning()
            ->actions([
                Action::make('review')
                    ->button()
                    ->label('Cek')
                    ->url(LeaveResource::getUrl('edit', ['record' => $leave])),
            ])
            ->sendToDatabase($admins);
    }
}