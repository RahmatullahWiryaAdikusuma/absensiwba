<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $officeId; // Tambahan

    // Terima officeId di constructor
    public function __construct($startDate, $endDate, $officeId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->officeId = $officeId;
    }

    public function collection()
    {
        return Attendance::with('user', 'user.position')
            ->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(), 
                Carbon::parse($this->endDate)->endOfDay()
            ])
            // Filter Lokasi Kantor (Jika dipilih)
            ->when($this->officeId, function ($query) {
                $query->whereHas('user.schedule', function ($q) {
                    $q->where('office_id', $this->officeId);
                });
            })
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Pegawai',
            'Jabatan',
            'Jam Masuk',
            'Jam Pulang',
            'Status',
            'Durasi Kerja',
        ];
    }

    public function map($attendance): array
    {
        return [
            Carbon::parse($attendance->created_at)->format('d-m-Y'),
            $attendance->user->name ?? '-',
            $attendance->user->position->name ?? '-',
            $attendance->start_time ?? '-',
            $attendance->end_time ?? '-',
            $attendance->isLate() ? 'Terlambat' : 'Tepat Waktu',
            $attendance->workDuration(),
        ];
    }
}