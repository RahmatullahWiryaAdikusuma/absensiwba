<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveCutiResource\Pages;
use App\Models\LeaveCuti;
use App\Models\Office; // Import
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;  
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter; // Import
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LeaveCutiResource extends Resource
{
    protected static ?string $model = LeaveCuti::class;
    protected static ?string $navigationLabel = 'Cuti Tahunan';
    protected static ?string $modelLabel = 'Cuti';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Manajemen Kehadiran';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        $schema = [
            Forms\Components\Section::make('Form Pengajuan Cuti') 
                ->description(fn() => 'Sisa Kuota Cuti Anda Tahun Ini: ' . Auth::user()->sisa_cuti . ' Hari')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Mulai Tanggal')
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set) { 
                             $set('sisa_cuti', Auth::user()->sisa_cuti);
                        })
                        ->rules([
                            fn (Get $get, ?Model $record) => function (string $attribute, $value, $fail) use ($get, $record) {
                                $user = Auth::user();
                                $endDate = $get('end_date') ?? $value;

                                // Cek Bentrok
                                $query = LeaveCuti::where('user_id', $user->id)
                                    ->where('status', '!=', 'rejected')
                                    ->where(function ($q) use ($value, $endDate) {
                                        $q->where('start_date', '<=', $endDate)
                                          ->where('end_date', '>=', $value);
                                    });

                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }

                                if ($query->exists()) {
                                    $fail('Anda sudah mengajukan cuti di tanggal ini.');
                                }
                            },
                        ]),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->required()
                        ->native(false)
                        ->afterOrEqual('start_date')
                        ->live()
                        ->rules([
                             fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                                $start = $get('start_date');
                                if(!$start) return;
                                
                                $startDate = \Carbon\Carbon::parse($start);
                                $endDate = \Carbon\Carbon::parse($value);
                                $days = $startDate->diffInDays($endDate) + 1;
                                 
                                if (Auth::user()->sisa_cuti < $days) {
                                    $fail("Sisa cuti tidak mencukupi. (Sisa: ".Auth::user()->sisa_cuti.", Pengajuan: $days hari)");
                                }
                             }
                        ]),

                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Cuti')
                        ->required()
                        ->columnSpanFull(),
                    
                    Forms\Components\FileUpload::make('surat_cuti')
                        ->label('Formulir Cuti (Jika ada)')
                        ->disk('public')
                        ->directory('surat-cuti')
                        ->visibility('public')
                        ->columnSpanFull(),
 
                    Forms\Components\Hidden::make('sisa_cuti')
                        ->default(fn() => Auth::user()->sisa_cuti),
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
                        ->required(),
                    Forms\Components\Textarea::make('note'),
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
                    ->label('Nama')
                    ->searchable(),
                  
                Tables\Columns\TextColumn::make('sisa_cuti')
                    ->label('Sisa (History)')
                    ->badge()
                    ->color('gray')
                    ->tooltip('Sisa cuti saat pengajuan ini dibuat'),

                Tables\Columns\TextColumn::make('start_date')->date('d M Y'),
                Tables\Columns\TextColumn::make('end_date')->date('d M Y'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([ 
                SelectFilter::make('status')
                    ->label('Status Ajuan')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]), 
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveCutis::route('/'),
            'create' => Pages\CreateLeaveCuti::route('/create'),
            'edit' => Pages\EditLeaveCuti::route('/{record}/edit'),
        ];
    }
}