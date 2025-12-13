<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Models\Leave;
use App\Models\Office; // Import
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter; // Import
use Filament\Tables\Actions\Action; 
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;
    
    protected static ?string $navigationLabel = 'Izin (Sakit/Lainnya)';
    protected static ?string $modelLabel = 'Izin';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Attendance Management';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        $schema = [
            Forms\Components\Section::make('Form Pengajuan Izin')
                ->description('Gunakan form ini untuk izin sakit atau keperluan mendesak (Tidak memotong kuota cuti).')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Mulai Tanggal')
                        ->required()
                        ->native(false),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->required()
                        ->native(false)
                        ->afterOrEqual('start_date'),

                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Izin')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('surat_izin')
                        ->label('Bukti / Surat Dokter')
                        ->disk('public')
                        ->directory('surat-izin')
                        ->visibility('public')
                        ->image() 
                        ->columnSpanFull(),
                ])
        ];

        if (Auth::user()->hasRole('super_admin')) {
            $schema[] = Forms\Components\Section::make('Approval (Admin Only)')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                        ])
                        ->default('pending')
                        ->required(),
                    Forms\Components\Textarea::make('note')
                        ->label('Catatan Admin')
                        ->placeholder('Alasan penolakan atau catatan tambahan'),
                ]);
        }

        return $form->schema($schema);
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
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date('d M Y')
                    ->label('Mulai'),

                Tables\Columns\TextColumn::make('end_date')
                    ->date('d M Y')
                    ->label('Selesai'),
                
                Tables\Columns\ImageColumn::make('surat_izin')
                    ->label('Bukti')
                    ->disk('public')
                    ->square()
                    ->url(fn ($record) => asset('storage/' . $record->surat_izin))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                // 1. Filter Status Ajuan
                SelectFilter::make('status')
                    ->label('Status Ajuan')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
                
                // 2. Filter Lokasi Kantor
                SelectFilter::make('office_id')
                    ->label('Lokasi Kantor')
                    ->options(Office::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return $query->whereHas('user.schedule', function (Builder $q) use ($data) {
                            $q->where('office_id', $data['value']);
                        });
                    })
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}