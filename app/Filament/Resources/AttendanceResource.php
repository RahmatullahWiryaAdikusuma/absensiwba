<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\Office; // Import Model Office
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn; 
use Filament\Tables\Filters\SelectFilter; // Import Filter
use Filament\Tables\Filters\Filter; // Import Filter
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationGroup = 'Attendance Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->disabled(),
            
            Forms\Components\FileUpload::make('start_image')
                ->label('Foto Masuk')
                ->disk('public')
                ->directory('attendance-photos')
                ->image()
                ->openable(),

            Forms\Components\FileUpload::make('end_image')
                ->label('Foto Pulang')
                ->disk('public')
                ->directory('attendance-photos')
                ->image()
                ->openable(),
        ]); 
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (!Auth::user()->hasRole('super_admin')) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([ 
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->searchable() // Fitur Pencarian Nama
                    ->sortable()
                    ->description(fn (Attendance $record) => $record->user->position->name ?? '-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y')  
                    ->sortable(),

                Tables\Columns\ColumnGroup::make('Kehadiran Masuk', [
                    ImageColumn::make('start_image')
                        ->label('Selfie Masuk')
                        ->disk('public')
                        ->circular()
                        ->defaultImageUrl(url('/images/placeholder.png'))
                        ->visibility('public'), 
                    
                    Tables\Columns\TextColumn::make('start_time')
                        ->label('Jam')
                        ->time('H:i')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                        ->weight('bold'),
                ])->alignCenter(),

                Tables\Columns\ColumnGroup::make('Kehadiran Pulang', [
                    ImageColumn::make('end_image')
                        ->label('Selfie Pulang')
                        ->disk('public')
                        ->circular()
                        ->defaultImageUrl(url('/images/placeholder.png')),

                    Tables\Columns\TextColumn::make('end_time')
                        ->label('Jam')
                        ->time('H:i')
                        ->placeholder('-')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                        ->weight('bold'),
                ])->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->isLate() ? 'Terlambat' : 'Tepat Waktu';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Tepat Waktu' => 'success',
                        'Terlambat' => 'danger',
                    })
                    ->description(fn (Attendance $record): string => $record->end_time ? 'Durasi: '.$record->workDuration() : 'Sedang Bekerja'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // FILTER LOKASI KANTOR
                SelectFilter::make('office_id')
                    ->label('Lokasi Kantor')
                    ->options(Office::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        // Filter user berdasarkan jadwal kantornya
                        return $query->whereHas('user.schedule', function (Builder $q) use ($data) {
                            $q->where('office_id', $data['value']);
                        });
                    })
                    ->searchable(),

                // FILTER TANGGAL
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}