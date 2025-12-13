<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['name' => 'Manager'],
            ['name' => 'Staff IT'],
            ['name' => 'Staff Admin'],
            ['name' => 'Staff Keuangan'],
            ['name' => 'Staff Marketing'],
            ['name' => 'Staff HRD'],
            ['name' => 'Security'],
            ['name' => 'Cleaning Service'],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                ['name' => $position['name']],
                $position
            );
        }
    }
}
