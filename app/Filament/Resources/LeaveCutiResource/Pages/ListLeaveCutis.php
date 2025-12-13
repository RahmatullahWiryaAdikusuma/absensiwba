<?php

namespace App\Filament\Resources\LeaveCutiResource\Pages;

use App\Filament\Resources\LeaveCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveCutis extends ListRecords
{
    protected static string $resource = LeaveCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
