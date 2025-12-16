<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlacementResource\Pages;
use App\Models\Placement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; // Tambahan
use Illuminate\Support\Facades\Auth;   // Tambahan PENTING

class PlacementResource extends Resource
{
    protected static ?string $model = Placement::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Manajemen Penempatan';
    protected static ?string $navigationLabel = 'Penempatan';
    protected static ?string $modelLabel = 'Penempatan';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penempatan')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Nama Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('office_id')
                            ->label('Lokasi Penempatan')
                            ->relationship('office', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('backup') // Pastikan nama kolom di DB 'is_backup' atau 'backup' sesuai migrasi
                            ->label('Tipe Karyawan')
                            ->options([
                                'reguler' => 'Reguler',
                                'backup' => 'Backup',
                            ])
                            ->default('reguler')
                            ->required()
                            ->native(false),
 
                        Forms\Components\Select::make('placement_status')
                            ->label('Status Penempatan')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
 
                        Forms\Components\TextInput::make('daily_rate')
                            ->label('Rate Harian')
                            ->prefix('Rp')
                            ->numeric()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Periode Kontrak')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->helperText('Kosongkan jika kontrak permanen/belum ditentukan'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table 
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load untuk performa
                return $query->with(['user.position', 'office']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
       
                Tables\Columns\TextColumn::make('user.position.name')
                    ->label('Jabatan')
                    ->searchable()  
                    ->sortable()
                    ->badge()  
                    ->color('gray'), 

                Tables\Columns\TextColumn::make('office.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-office'),

                Tables\Columns\TextColumn::make('backup')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        // Handle jika data tersimpan sebagai angka/boolean
                        if ($state === '1' || $state === 1 || $state === true) return 'backup';
                        if ($state === '0' || $state === 0 || $state === false) return 'reguler';
                        return $state;
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'reguler', '0', 0, false => 'info',
                        'backup', '1', 1, true => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('daily_rate')
                    ->label('Rate Harian')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->placeholder('Sekarang'),

                Tables\Columns\TextColumn::make('placement_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('placement_status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
                SelectFilter::make('backup')
                    ->label('Tipe')
                    ->options([
                        'reguler' => 'Reguler',
                        'backup' => 'Backup',
                    ]),
                SelectFilter::make('office_id')
                    ->label('Lokasi Kantor')
                    ->relationship('office', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('position')
                    ->label('Jabatan')
                    ->relationship('user.position', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                // PENGAMANAN TOMBOL EDIT & DELETE
                Tables\Actions\EditAction::make()
                    ->hidden(fn () => !Auth::user()->hasRole('super_admin')), 
                
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn () => !Auth::user()->hasRole('super_admin')),
            ])
            ->bulkActions([
                // PENGAMANAN BULK DELETE
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn () => !Auth::user()->hasRole('super_admin')),
                ]),
            ]);
    }

    // --- PENGAMANAN LEVEL RESOURCE (URL & TOMBOL CREATE) ---

    // Mematikan tombol "New Placement" untuk selain super_admin
    public static function canCreate(): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    // Mematikan akses edit untuk selain super_admin
    public static function canEdit(Model $record): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    // Mematikan akses delete untuk selain super_admin
    public static function canDelete(Model $record): bool
    {
        return Auth::user()->hasRole('super_admin');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlacements::route('/'),
            'create' => Pages\CreatePlacement::route('/create'),
            'edit' => Pages\EditPlacement::route('/{record}/edit'),
        ];
    }
}