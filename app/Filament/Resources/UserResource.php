<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Office; // Import
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter; // Import Ternary
use Filament\Tables\Filters\SelectFilter; // Import Select
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Select::make('position_id')
                    ->label('Jabatan / Posisi')
                    ->relationship('position', 'name') 
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Jabatan Baru')
                            ->required(),
                    ])
                    ->editOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Edit Nama Jabatan')
                            ->required(),
                    ])
                    ->required(),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Placeholder::make('sisa_cuti_info')
                    ->label('Sisa Cuti Tahunan')
                    ->content(fn ($record) => $record ? $record->sisa_cuti . ' Hari' : '-'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('position.name') 
                    ->label('Jabatan')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                ToggleColumn::make('is_active')
                ->label('Status Aktif')
                ->onColor('success')
                ->offColor('danger')
                ->sortable(),
                Tables\Columns\TextColumn::make('sisa_cuti')
                ->label('Sisa Cuti')
                ->badge()
                ->color(fn ($state) => $state > 5 ? 'success' : 'warning'),  
                Tables\Columns\TextColumn::make('roles.name')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // 1. FILTER STATUS AKUN
                TernaryFilter::make('is_active')
                    ->label('Status Akun')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Non Aktif'),

                // 2. FILTER LOKASI KANTOR (Via Schedule)
                SelectFilter::make('office_id')
                    ->label('Lokasi Kantor')
                    ->options(Office::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        // Cari user yang punya jadwal di kantor tersebut
                        return $query->whereHas('schedule', function (Builder $q) use ($data) {
                            $q->where('office_id', $data['value']);
                        });
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}