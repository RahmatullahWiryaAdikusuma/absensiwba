<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions; 
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use App\Models\User;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('resetCuti') 
                ->label('Reset Cuti (12)')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    User::query()->update(['leave_balance' => 12]);
                    Notification::make()->title('Reset Berhasil')->success()->send();
                }),
        ];
    }
}