<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select; // Import Select
use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\Office; // Import Model Office
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TOMBOL TAMBAH
            Actions\Action::make('tambah_presensi')
                ->label('Tambah Presensi')
                ->url(route('presensi'))
                ->color('success'),

            // TOMBOL EXPORT EXCEL
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->required()
                        ->default(now()->startOfMonth()),
                    DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->required()
                        ->default(now()),
                    // Filter Kantor
                    Select::make('office_id')
                        ->label('Filter Kantor')
                        ->options(Office::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Semua Kantor'),
                ])
                ->action(function (array $data) {
                    $startDate = $data['start_date'];
                    $endDate = $data['end_date'];
                    $officeId = $data['office_id']; // Ambil office_id
                    
                    return Excel::download(
                        // Kirim officeId ke Export Class
                        new AttendanceExport($startDate, $endDate, $officeId), 
                        'Laporan_Presensi_' . Carbon::now()->format('Ymd_His') . '.xlsx'
                    );
                }),

            // TOMBOL EXPORT PDF
            Actions\Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-printer')
                ->color('danger')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->required()
                        ->default(now()->startOfMonth()),
                    DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->required()
                        ->default(now()),
                    // Filter Kantor
                    Select::make('office_id')
                        ->label('Filter Kantor')
                        ->options(Office::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Semua Kantor'),
                ])
                ->action(function (array $data) {
                    $startDate = $data['start_date'];
                    $endDate = $data['end_date'];
                    $officeId = $data['office_id'];

                    // Logic Query dengan Filter Kantor
                    $query = Attendance::with(['user', 'user.position'])
                        ->whereBetween('created_at', [
                            Carbon::parse($startDate)->startOfDay(), 
                            Carbon::parse($endDate)->endOfDay()
                        ]);

                    // Jika user memilih kantor
                    if ($officeId) {
                        $query->whereHas('user.schedule', function ($q) use ($officeId) {
                            $q->where('office_id', $officeId);
                        });
                    }

                    $attendances = $query->latest()->get();

                    // Ambil nama kantor untuk judul laporan
                    $officeName = $officeId ? Office::find($officeId)?->name : 'Semua Kantor';

                    $pdf = Pdf::loadView('reports.attendance-pdf', [
                        'attendances' => $attendances,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'officeName' => $officeName, // Kirim nama kantor ke view
                    ]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Laporan_Presensi_' . Carbon::now()->format('Ymd_His') . '.pdf');
                }),

            Actions\CreateAction::make(),
        ];
    }
}