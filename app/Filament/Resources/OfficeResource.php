<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\OfficeResource\Pages;
use App\Filament\Resources\OfficeResource\RelationManagers;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Humaidem\FilamentMapPicker\Fields\OSMMap;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Forms\Set;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Office Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([ 
                Section::make('Informasi Kantor')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kantor / Lokasi')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: WBA Pusat'),
                            
                        TextInput::make('address')
                            ->label('Alamat')
                            ->required()    
                            ->columnSpanFull(),
                    ]),
 
                Section::make('Titik Lokasi Absensi')
                    ->description('Anda bisa menambahkan banyak titik koordinat untuk kantor ini.')
                    ->schema([
                        Repeater::make('locations')  
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Titik Lokasi')
                                    ->required()
                                    ->placeholder('Misal: Gerbang Depan')
                                    ->columnSpanFull(),
 
                                OSMMap::make('location_map') 
                                    ->label('Pilih Lokasi di Peta')
                                    ->showMarker()
                                    ->draggable()
                                    ->extraControl([
                                        'zoomDelta'           => 1,
                                        'zoomSnap'            => 0.25,
                                        'wheelPxPerZoomLevel' => 60
                                    ]) 
                                    
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Get $get, Set $set, ?array $state) {
                                        
                                        $lat = $get('latitude');
                                        $lng = $get('longitude');
                                        
                                        if ($lat && $lng) {
                                            $set('location_map', ['lat' => $lat, 'lng' => $lng]);
                                        }
                                    }) 
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (is_array($state)) {
                                            $set('latitude', $state['lat'] ?? null);
                                            $set('longitude', $state['lng'] ?? null);
                                        }
                                    })
                                    ->tilesUrl('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
                                    ->columnSpanFull(),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('latitude')
                                            ->label('Latitude')
                                            ->required()
                                            ->numeric(),
                                            
                                        TextInput::make('longitude')
                                            ->label('Longitude')
                                            ->required()
                                            ->numeric(),

                                        TextInput::make('radius')
                                            ->label('Radius (Meter)')
                                            ->required()
                                            ->numeric()
                                            ->default(50)
                                            ->minValue(10),
                                    ]),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->addActionLabel('Tambah Titik Lokasi')
                            ->defaultItems(1)
                            ->grid(1)
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kantor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('locations_count')
                    ->counts('locations')
                    ->label('Jml. Titik Absen')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}