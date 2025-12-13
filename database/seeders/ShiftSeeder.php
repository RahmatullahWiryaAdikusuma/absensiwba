<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Shift Pagi',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
            ],
            [
                'name' => 'Shift Siang',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
            ],
            [
                'name' => 'Shift Malam',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}
