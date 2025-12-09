<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification; // Ini hanya untuk notif popup ke Admin sendiri
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
// === IMPORT EVENT ===
use App\Events\LeaveStatusUpdated;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [ Actions\DeleteAction::make() ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldStatus = $record->status;

        // 1. Logic Potong Cuti (Jika Approved)
        if ($oldStatus !== 'approved' && $data['status'] === 'approved') {
            $user = $record->user;
            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);
            $daysRequested = $start->diffInDays($end) + 1; 

            if ($user->leave_balance < $daysRequested) {
                Notification::make()
                    ->title('Gagal: Saldo Kurang!')
                    ->danger()->persistent()->send();
                $this->halt(); 
            }

            $user->decrement('leave_balance', $daysRequested);
            // Notif Popup sesaat untuk Admin yang sedang klik
            Notification::make()->title('Cuti Disetujui & Saldo Terpotong')->success()->send();
        }

        // 2. Simpan Data
        $record->update($data);

        // 3. 🔥 TRIGGER EVENT JIKA STATUS BERUBAH
        // Biarkan Listener yang mengirim notif Lonceng ke Karyawan
        if ($oldStatus !== $data['status']) {
            event(new LeaveStatusUpdated($record));
        }

        return $record;
    }
}