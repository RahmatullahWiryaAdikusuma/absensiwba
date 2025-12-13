<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user karyawan yang memiliki schedule
        $karyawan = User::where('email', 'karyawan@kantor.com')->first();
        
        if (!$karyawan) {
            $this->command->warn('User karyawan tidak ditemukan.');
            return;
        }

        $schedule = Schedule::where('user_id', $karyawan->id)->first();
        
        if (!$schedule) {
            $this->command->warn('Schedule untuk karyawan tidak ditemukan. Jalankan ScheduleSeeder terlebih dahulu.');
            return;
        }

        // Ambil koordinat dari office location
        $latitude = $schedule->officeLocation?->latitude ?? -6.2088;
        $longitude = $schedule->officeLocation?->longitude ?? 106.8456;

        // Buat data absensi untuk 7 hari terakhir
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            // Skip weekend (Sabtu & Minggu)
            if ($date->isWeekend()) {
                continue;
            }

            $scheduleStartTime = Carbon::parse($schedule->shift->start_time);
            $scheduleEndTime = Carbon::parse($schedule->shift->end_time);
            
            // Set tanggal untuk waktu schedule
            $scheduleStart = $date->copy()->setTime($scheduleStartTime->hour, $scheduleStartTime->minute);
            $scheduleEnd = $date->copy()->setTime($scheduleEndTime->hour, $scheduleEndTime->minute);
            
            // Jika end_time lebih kecil dari start_time (shift malam), tambah 1 hari
            if ($scheduleEndTime->lessThan($scheduleStartTime)) {
                $scheduleEnd->addDay();
            }

            // Check-in: sedikit random, bisa tepat waktu atau telat
            $checkInTime = $scheduleStart->copy()->addMinutes(rand(-5, 15));
            
            // Check-out: sedikit random
            $checkOutTime = $scheduleEnd->copy()->addMinutes(rand(-10, 5));

            // Koordinat dengan sedikit variasi (dalam radius)
            $startLat = $latitude + (rand(-50, 50) / 1000000);
            $startLong = $longitude + (rand(-50, 50) / 1000000);
            $endLat = $latitude + (rand(-50, 50) / 1000000);
            $endLong = $longitude + (rand(-50, 50) / 1000000);

            Attendance::firstOrCreate(
                [
                    'user_id' => $karyawan->id,
                    'start_time' => $checkInTime->format('Y-m-d H:i:s'),
                ],
                [
                    'user_id' => $karyawan->id,
                    'schedule_latitude' => $latitude,
                    'schedule_longitude' => $longitude,
                    'schedule_start_time' => $scheduleStartTime->format('H:i:s'),
                    'schedule_end_time' => $scheduleEndTime->format('H:i:s'),
                    'start_latitude' => $startLat,
                    'start_longitude' => $startLong,
                    'start_time' => $checkInTime,
                    'start_image' => 'attendance/start_' . $date->format('Ymd') . '.jpg',
                    'end_latitude' => $endLat,
                    'end_longitude' => $endLong,
                    'end_time' => $checkOutTime,
                    'end_image' => 'attendance/end_' . $date->format('Ymd') . '.jpg',
                ]
            );
        }

        $this->command->info('Attendance seeder completed successfully!');
    }
}
