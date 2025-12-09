<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Events\LeaveRequested;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::user()->id;
        $data['status'] = 'pending';
        return $data;
    }

    // === TRIGGER EVENT SETELAH DATA TERBUAT ===
    protected function afterCreate(): void
    {
        // Panggil Event, biarkan Listener yang kirim notif ke semua Super Admin
        event(new LeaveRequested($this->record));
    }
}