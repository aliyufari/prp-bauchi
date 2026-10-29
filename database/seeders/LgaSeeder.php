<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class LgaSeeder extends Seeder
{
    private const LGAS = [
        'Bauchi North' => ['Gamawa', 'Giade', 'Itas/Gadau', "Jama'are", 'Katagum', 'Shira', 'Zaki'],
        'Bauchi Central' => ['Dambam', 'Darazo', 'Ganjuwa', 'Misau', 'Ningi', 'Warji'],
        'Bauchi South' => ['Alkaleri', 'Bauchi', 'Bogoro', 'Dass', 'Kirfi', 'Tafawa Balewa', 'Toro'],
    ];

    public function run(): void
    {
        $zones = Zone::pluck('id', 'name');

        foreach (self::LGAS as $zoneName => $names) {
            $zoneId = $zones->get($zoneName) ?? throw new \RuntimeException("Zone '{$zoneName}' is missing. Run ZoneSeeder first.");

            foreach ($names as $name) {
                Lga::firstOrCreate(['zone_id' => $zoneId, 'name' => $name]);
            }
        }
    }
}