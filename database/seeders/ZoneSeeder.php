<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    private const ZONES = ['Bauchi North', 'Bauchi Central', 'Bauchi South'];

    public function run(): void
    {
        $state = State::where('name', 'Bauchi')->firstOrFail();

        foreach (self::ZONES as $name) {
            Zone::firstOrCreate(['state_id' => $state->id, 'name' => $name]);
        }
    }
}