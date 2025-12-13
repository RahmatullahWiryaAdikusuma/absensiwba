<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;

class OfficeLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil data office
        $jakartaOffice = Office::where('name', 'Kantor Pusat Jakarta')->first();
        $bandungOffice = Office::where('name', 'Kantor Cabang Bandung')->first();
        $surabayaOffice = Office::where('name', 'Kantor Cabang Surabaya')->first();

        $locations = [
            // Lokasi Kantor Jakarta
            [
                'office_id' => $jakartaOffice?->id,
                'name' => 'Gedung Utama Jakarta',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100, // dalam meter
            ],
            
            // Lokasi Kantor Bandung
            [
                'office_id' => $bandungOffice?->id,
                'name' => 'Gedung Utama Bandung',
                'latitude' => -6.9175,
                'longitude' => 107.6191,
                'radius' => 100,
            ],
            
            // Lokasi Kantor Surabaya
            [
                'office_id' => $surabayaOffice?->id,
                'name' => 'Gedung Utama Surabaya',
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'radius' => 100,
            ],
        ];

        foreach ($locations as $location) {
            if ($location['office_id']) {
                OfficeLocation::firstOrCreate(
                    [
                        'office_id' => $location['office_id'],
                        'name' => $location['name']
                    ],
                    $location
                );
            }
        }
    }
}
