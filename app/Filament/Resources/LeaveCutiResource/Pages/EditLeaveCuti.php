<?php

namespace App\Filament\Resources\LeaveCutiResource\Pages;

use App\Filament\Resources\LeaveCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveCuti extends EditRecord
{
    protected static string $resource = LeaveCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
