<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
            PositionSeeder::class,
            ShiftSeeder::class,
            OfficeSeeder::class,
            OfficeLocationSeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'superadmin@kantor.com'], // Cek email biar ga duplikat
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true, 
            ]
        );

        $user->assignRole('super_admin');
        
        $karyawan = User::create([
            'name' => 'Karyawan 1',
            'email' => 'karyawan@kantor.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $karyawan->assignRole('karyawan');

        $this->call([
            ScheduleSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}