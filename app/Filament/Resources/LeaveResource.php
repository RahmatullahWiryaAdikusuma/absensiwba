<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Models\Leave;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    Forms\Components\DatePicker::make('start_date')->required(),
                    Forms\Components\DatePicker::make('end_date')->required(),
                    Forms\Components\Textarea::make('reason')->required()->columnSpanFull(),
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