<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Models\Leave;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; // Import Model
use Illuminate\Support\Facades\Auth;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;
    protected static ?string $navigationIcon = 'heroicon-c-x-circle';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationGroup = 'Attendance Management';

    public static function form(Form $form): Form
    {
        $schema = [
            Forms\Components\Section::make('Detail Pengajuan')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false)
                        ->live()
                        ->rules([
                            fn (Get $get, ?Model $record) => function (string $attribute, $value, $fail) use ($get, $record) {
                                $user = Auth::user();
                                $endDate = $get('end_date') ?? $value;

                                // Logika Cek Bentrok yang Lebih Simpel & Akurat
                                $query = Leave::where('user_id', $user->id)
                                    ->where('status', '!=', 'rejected') // Abaikan yang ditolak
                                    ->where(function ($q) use ($value, $endDate) {
                                        $q->where('start_date', '<=', $endDate)
                                          ->where('end_date', '>=', $value);
                                    });

                                // PENTING: Kecualikan record ini sendiri jika sedang mode Edit
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }

                                if ($query->exists()) {
                                    $fail('Tanggal cuti bertabrakan dengan pengajuan Anda yang lain.');
                                }
                            },
                        ]),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->native(false)
                        ->afterOrEqual('start_date')
                        ->live()
                        ->rules([
                            fn (Get $get, ?Model $record) => function (string $attribute, $value, $fail) use ($get, $record) {
                                $user = Auth::user();
                                $startDate = $get('start_date');
                                
                                if (!$startDate) return;

                                $query = Leave::where('user_id', $user->id)
                                    ->where('status', '!=', 'rejected')
                                    ->where(function ($q) use ($startDate, $value) {
                                        $q->where('start_date', '<=', $value)
                                          ->where('end_date', '>=', $startDate);
                                    });

                                // PENTING: Kecualikan record ini sendiri jika sedang mode Edit
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }

                                if ($query->exists()) {
                                    $fail('Tanggal cuti bertabrakan dengan pengajuan Anda yang lain.');
                                }
                            },
                        ]),

                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan')
                        ->required()
                        ->columnSpanFull(),
                ])
        ];

        // HANYA SUPER ADMIN YANG BISA LIHAT FORM APPROVAL
        if (Auth::user()->hasRole('super_admin')) {
            $schema[] =
            Forms\Components\Section::make('Approval (Admin Only)')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'pending' => 'Pending',
                        ])->required(),
                    Forms\Components\Textarea::make('note')->columnSpanFull(),
                ]);
        }

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Karyawan cuma bisa lihat punya sendiri
                if (!Auth::user()->hasRole('super_admin')) {
                    $query->where('user_id', Auth::user()->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->description(fn (Leave $record) => $record->user->position->name ?? '-')
                    ->formatStateUsing(fn ($state, $record) => "{$state} (" . ($record->user->position->name ?? 'Staff') . ")"),
                
                Tables\Columns\TextColumn::make('user.leave_balance')
                    ->label('Sisa Cuti')
                    ->badge()
                    ->color(fn ($state) => $state > 5 ? 'success' : ($state > 2 ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) { 'approved' => 'success', 'rejected' => 'danger', default => 'gray' }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([ Tables\Actions\EditAction::make(), ])
            ->bulkActions([ Tables\Actions\DeleteBulkAction::make(), ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}