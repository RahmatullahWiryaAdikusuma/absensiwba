<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
        return $form->schema([]); // Form kosong karena absen via HP
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // User biasa cuma bisa lihat datanya sendiri
                if (!Auth::user()->hasRole('super_admin')) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([
                // === 1. KOLOM PEGAWAI (Sekarang di Pojok Kiri) ===
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        // Ambil nama jabatan dari relasi
                        // $state = Nama User (contoh: Adikusuma)
                        $jabatan = $record->user->position->name ?? '-';
                        return "{$state} ({$jabatan})";
                    }),

                // === 2. KOLOM TANGGAL ===
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date() // Menampilkan Tanggal Saja
                    ->sortable(),

                // === 3. JAM MASUK & PULANG ===
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Waktu Datang')
                    ->time(), // Menampilkan Jam Saja

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Waktu Pulang')
                    ->time(),

                // === 4. STATUS TERLAMBAT ===
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
                    ->description(fn (Attendance $record): string => 'Durasi: '.$record->workDuration()),
            ])
            ->defaultSort('created_at', 'desc')
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