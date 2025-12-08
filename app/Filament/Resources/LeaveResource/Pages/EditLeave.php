<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [ Actions\DeleteAction::make() ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // LOGIKA POTONG CUTI
        if ($record->status !== 'approved' && $data['status'] === 'approved') {
            $user = $record->user;
            
            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);
            $daysRequested = $start->diffInDays($end) + 1; 

            if ($user->leave_balance < $daysRequested) {
                Notification::make()
                    ->title('Gagal: Saldo Cuti Kurang!')
                    ->body("Sisa: {$user->leave_balance}, Diminta: {$daysRequested}")
                    ->danger()->persistent()->send();
                $this->halt(); 
            }

            $user->decrement('leave_balance', $daysRequested);
            $user->refresh();

            // Notif Popup Sukses buat Admin
            Notification::make()
                ->title('Berhasil Disetujui')
                ->body("Sisa cuti user sekarang: {$user->leave_balance} hari.")
                ->success()->send();
        }

        // SIMPAN DATA
        $record->update($data);

        // === NOTIFIKASI KE KARYAWAN (Sesuai Status) ===
        $pegawai = $record->user;

        if ($data['status'] === 'approved') {
             Notification::make()
                ->title('Cuti Disetujui ✅')
                ->body("Pengajuan cuti Anda untuk tanggal {$data['start_date']} telah disetujui.")
                ->success()
                ->sendToDatabase($pegawai); 
        
        } elseif ($data['status'] === 'rejected') {
             Notification::make()
                ->title('Cuti Ditolak ❌')
                ->body("Maaf, pengajuan cuti Anda ditolak. Alasan: " . ($data['note'] ?? '-'))
                ->danger()
                ->sendToDatabase($pegawai);
        }

        return $record;
    }
}