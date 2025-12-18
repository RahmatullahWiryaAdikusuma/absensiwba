<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'schedule_latitude',
        'schedule_longitude',
        'schedule_start_time',
        'schedule_end_time',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'start_time',
        'end_time',
        'start_image',
        'end_image',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLate(): bool
    {
        // Jika data jam tidak lengkap, anggap tidak telat
        if (!$this->schedule_start_time || !$this->start_time) {
            return false;
        }

        // 1. Ambil Tanggal dari start_time (Contoh: 2025-12-15)
        $attendanceDate = Carbon::parse($this->start_time)->format('Y-m-d');
        
        // 2. Ambil Jam dari schedule (Contoh: 08:00:00)
        // Kita parse dulu untuk memastikan formatnya bersih H:i:s
        $scheduleTimeOnly = Carbon::parse($this->schedule_start_time)->format('H:i:s');

        // 3. GABUNGKAN Tanggal Absen + Jam Jadwal
        // Hasil: 2025-12-15 08:00:00
        $fixedScheduleTime = Carbon::parse($attendanceDate . ' ' . $scheduleTimeOnly);

        // 4. Tambahkan toleransi keterlambatan (misal 1 menit) jika perlu
        // $fixedScheduleTime->addMinutes(1); 

        // 5. Bandingkan
        return Carbon::parse($this->start_time)->gt($fixedScheduleTime);
    }

    /**
     * Hitung durasi kerja (PERBAIKAN: Support multi-day attendance)
     */
    public function workDuration(): string
    {
        if (!$this->end_time) {
            return '-';
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        $diff = $start->diff($end);

        // Format dengan total hours dan minutes (support > 24 jam)
        $days = $diff->days;
        $hours = $diff->h;
        $minutes = $diff->i;
        
        $totalHours = ($days * 24) + $hours;
        
        return "{$totalHours} Jam {$minutes} Menit";
    }

    /**
     * Get display date range untuk attendance
     */
    public function getWorkDateRange(): string
    {
        if (!$this->end_time) {
            return Carbon::parse($this->start_time)->format('d M Y');
        }

        $startDate = Carbon::parse($this->start_time)->format('d M Y');
        $endDate = Carbon::parse($this->end_time)->format('d M Y');

        if ($startDate === $endDate) {
            return $startDate;
        }

        return "{$startDate} s/d {$endDate}";
    }




}
