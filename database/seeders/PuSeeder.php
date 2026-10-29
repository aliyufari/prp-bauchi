<?php

namespace Database\Seeders;

use App\Models\Pu;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class PuSeeder extends Seeder
{
    private const PER_WARD = 5;

    public function run(): void
    {
        $n = Pu::count();

        // Stable order so codes are assigned the same way on every fresh seed.
        $wards = Ward::query()
            ->join('lgas', 'lgas.id', '=', 'wards.lga_id')
            ->orderBy('lgas.name')->orderBy('wards.name')
            ->select('wards.*')
            ->get();

        foreach ($wards as $ward) {
            foreach (range(1, self::PER_WARD) as $i) {
                $pu = Pu::firstOrNew(['ward_id' => $ward->id, 'name' => "Polling Unit {$i}"]);

                if (! $pu->exists) {
                    $pu->fill([
                        'code' => sprintf('PU-%04d', ++$n), // unique across the whole table
                        'registered_voters' => random_int(300, 1500),
                    ])->save();
                }
            }
        }
    }
}