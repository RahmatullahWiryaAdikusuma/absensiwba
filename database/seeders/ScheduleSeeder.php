<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\OfficeLocation;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user dengan role karyawan
        $karyawan = User::where('email', 'karyawan@kantor.com')->first();
        
        if (!$karyawan) {
            $this->command->warn('User karyawan tidak ditemukan. Pastikan DatabaseSeeder sudah dijalankan terlebih dahulu.');
            return;
        }

        // Ambil data shift, office, dan location
        $shiftPagi = Shift::where('name', 'Shift Pagi')->first();
        $officeJakarta = Office::where('name', 'Kantor Pusat Jakarta')->first();
        $locationJakarta = OfficeLocation::where('office_id', $officeJakarta?->id)->first();

        if ($shiftPagi && $officeJakarta && $locationJakarta) {
            Schedule::firstOrCreate(
                [
                    'user_id' => $karyawan->id,
                    'shift_id' => $shiftPagi->id,
                ],
                [
                    'user_id' => $karyawan->id,
                    'shift_id' => $shiftPagi->id,
                    'office_id' => $officeJakarta->id,
                    'office_location_id' => $locationJakarta->id,
                    'is_wfa' => false,
                    'is_banned' => false,
                ]
            );
        }

        // Tambahkan contoh schedule untuk user lain jika ada
        $otherUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'karyawan');
        })->where('id', '!=', $karyawan->id)->take(5)->get();

        foreach ($otherUsers as $user) {
            $randomShift = Shift::inRandomOrder()->first();
            $randomOffice = Office::inRandomOrder()->first();
            $randomLocation = OfficeLocation::where('office_id', $randomOffice?->id)->first();

            if ($randomShift && $randomOffice) {
                Schedule::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'shift_id' => $randomShift->id,
                    ],
                    [
                        'user_id' => $user->id,
                        'shift_id' => $randomShift->id,
                        'office_id' => $randomOffice->id,
                        'office_location_id' => $randomLocation?->id,
                        'is_wfa' => rand(0, 1) == 1,
                        'is_banned' => false,
                    ]
                );
            }
        }
    }
}
