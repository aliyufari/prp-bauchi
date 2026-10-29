<?php

namespace Database\Seeders;

use App\Models\Lga;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class WardSeeder extends Seeder
{
    private const PER_LGA = 5;

    public function run(): void
    {
        Lga::all()->each(function (Lga $lga) {
            foreach (range(1, self::PER_LGA) as $n) {
                Ward::firstOrCreate(['lga_id' => $lga->id, 'name' => "Ward {$n}"]);
            }
        });
    }
}