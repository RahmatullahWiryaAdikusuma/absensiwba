<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\ScheduleResource\Pages;
use App\Filament\Resources\ScheduleResource\RelationManagers;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use App\Models\OfficeLocation;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-m-calendar-days';
    protected static ?int $navigationSort = 7;

    protected static ?string $navigationGroup = 'Attendance Management';

        public static function form(Form $form): Form
    {
                            return $form
                                ->schema([
                            Forms\Components\Group::make()
                                ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                            Forms\Components\Toggle::make('is_banned'),
                            
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->required(),

                            Forms\Components\Select::make('shift_id')
                                ->relationship('shift', 'name')
                                ->required(), 

                            Forms\Components\Select::make('office_id')
                                ->relationship('office', 'name')
                                ->searchable()
                                ->preload()
                                ->live()  
                                ->afterStateUpdated(function (Set $set) {
                                    $set('office_location_id', null);  
                                })
                                ->required(), 
                
                            Forms\Components\Select::make('office_location_id')
                                ->label('Titik Lokasi Absen')
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
                                ->required()
                                ->visible(fn (Get $get) => $get('office_id') !== null), 

                            Forms\Components\Toggle::make('is_wfa'),
                        ])
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
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_banned')
                    ->hidden(fn () => !Auth::user()->hasRole('super_admin')),
                Tables\Columns\BooleanColumn::make('is_wfa')
                    ->label('WFA'),
                Tables\Columns\TextColumn::make('shift.name')
                    ->description(fn (Schedule $record): string => $record->shift->start_time.' - '.$record->shift->end_time)
                    ->sortable(),
                Tables\Columns\TextColumn::make('office.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
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
