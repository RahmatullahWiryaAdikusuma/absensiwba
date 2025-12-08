<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ActivityLogResource extends Resource
{
     
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationGroup = 'System Management';
    protected static ?string $navigationLabel = 'Activity Logs';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('causer_type')
                    ->label('Tipe Pelaku'),
                Forms\Components\TextInput::make('causer_id')
                    ->label('ID Pelaku'),
                Forms\Components\KeyValue::make('properties.attributes')
                    ->label('Data Baru'),
                Forms\Components\KeyValue::make('properties.old')
                    ->label('Data Lama'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
           
            ->modifyQueryUsing(fn ($query) => Auth::user()->hasRole('super_admin') ? $query : $query->whereRaw('1 = 0'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),
                 
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Pelaku (Admin)')
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('event')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Target Data')
                    ->formatStateUsing(function ($state) { 
                        return str_replace('App\\Models\\', '', $state);
                    }),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),  
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageActivityLogs::route('/'),
        ];
    }
}