<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;  
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; 
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
        
        if ($oldStatus !== 'approved' && $data['status'] === 'approved') {
            
            // Hitung durasi hari
            $start = Carbon::parse($data['start_date']);
            $end = Carbon::parse($data['end_date']);
            $daysRequested = $start->diffInDays($end) + 1; 

            $isSuccess = $record->user->deductLeaveBalance($daysRequested);
            if (! $isSuccess) {
                Notification::make()
                    ->title('Gagal: Saldo Cuti Kurang!')
                    ->body("Sisa: {$record->user->leave_balance}, Diminta: {$daysRequested}")
                    ->danger()
                    ->persistent()
                    ->send();
                
                $this->halt(); 
            }
            Notification::make()
                ->title('Berhasil Disetujui')
                ->body("Saldo cuti berhasil dipotong.")
                ->success()
                ->send();
        }
        $record->update($data);

        if ($oldStatus !== $data['status']) {
            event(new \App\Events\LeaveStatusUpdated($record));
        }

        return $record;
    }
}