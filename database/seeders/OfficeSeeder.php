<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offices = [
            [
                'name' => 'Kantor Pusat Jakarta',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10220',
            ],
            [
                'name' => 'Kantor Cabang Bandung',
                'address' => 'Jl. Asia Afrika No. 45, Bandung, Jawa Barat 40111',
            ],
            [
                'name' => 'Kantor Cabang Surabaya',
                'address' => 'Jl. Pemuda No. 67, Surabaya, Jawa Timur 60271',
            ],
        ];

        foreach ($offices as $office) {
            Office::firstOrCreate(
                ['name' => $office['name']],
                $office
            );
        }
    }
}
