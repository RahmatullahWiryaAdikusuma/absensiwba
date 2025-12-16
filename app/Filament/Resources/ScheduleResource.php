<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use App\Models\OfficeLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;   
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;
    protected static ?string $navigationLabel = 'Jadwal Masuk';
    protected static ?string $modelLabel = 'Jadwal Masuk';
    protected static ?string $navigationIcon = 'heroicon-m-calendar-days';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationGroup = 'Manajemen Kehadiran';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        // BAGIAN 1: DATA UTAMA JADWAL
                        Forms\Components\Section::make('Informasi Jadwal')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Nama Karyawan')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('shift_id')
                                    ->label('Shift Kerja')
                                    ->relationship('shift', 'name')
                                    ->required(), 

                                Forms\Components\Select::make('office_id')
                                    ->label('Kantor Pusat')
                                    ->relationship('office', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()  
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('office_location_id', null);  
                                    })
                                    ->required(), 
                
                                Forms\Components\Select::make('office_location_id')
                                    ->label('Titik Lokasi Absen (Spesifik)')
                                    ->options(function (Get $get): Collection {
                                        $officeId = $get('office_id');  

                                        if (! $officeId) {
                                            return collect();  
                                        }

                                        return OfficeLocation::where('office_id', $officeId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get) => $get('office_id') !== null), 
                            ])->columns(2),

                        Forms\Components\Section::make('Status Kepegawaian & Akses')
                            ->description('Atur hak akses absen khusus untuk karyawan ini.')
                            ->schema([
                                Forms\Components\Toggle::make('is_wfa')
                                    ->label('Mode WFA / Dinas Luar')
                                    ->helperText('Jika AKTIF: Karyawan bebas absen dari lokasi mana saja (GPS Radius diabaikan).')
                                    ->onColor('success')
                                    ->offColor('gray'),

                                Forms\Components\Toggle::make('is_banned')
                                    ->label('Banned / Non-Aktif')
                                    ->helperText('Jika AKTIF: Karyawan DIBLOKIR dan tidak bisa melakukan absen sama sekali.')
                                    ->onColor('danger')
                                    ->offColor('gray'),
                            ])->columns(2),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('super_admin');
                if (!$is_super_admin) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Shift')
                    ->description(fn (Schedule $record): string => 
                        \Carbon\Carbon::parse($record->shift->start_time)->format('H:i') . ' - ' . 
                        \Carbon\Carbon::parse($record->shift->end_time)->format('H:i')
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('office.name')
                    ->label('Lokasi')   
                    ->sortable()
                    ->icon('heroicon-m-map-pin'),

                Tables\Columns\IconColumn::make('is_wfa')
                    ->label('WFA')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\ToggleColumn::make('is_banned')
                    ->label('Banned')
                    ->onColor('danger')
                    ->offColor('gray')
                    ->hidden(fn () => !Auth::user()->hasRole('super_admin')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('office_id')
                    ->label('Filter Kantor')
                    ->relationship('office', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('is_wfa')
                    ->label('Filter WFA'),

                Tables\Filters\TernaryFilter::make('is_banned')
                    ->label('Filter Banned'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [ 
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}   