<?php

namespace Database\Seeders;

use App\Enums\ConstituencyType;
use App\Models\Constituency;
use App\Models\State;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ConstituencySeeder extends Seeder
{
    public function run(): void
    {
        $state = State::where('name', 'Bauchi')->firstOrFail();

        $this->make($state, 'Bauchi Governorship', ConstituencyType::GOVERNORSHIP, 'Governor of Bauchi State');

        // Bauchi's three senatorial districts, named after the zones.
        Zone::where('state_id', $state->id)->orderBy('name')->get()->each(
            fn (Zone $zone) => $this->make($state, "{$zone->name} Senatorial District", ConstituencyType::SENATORIAL, "Senator for {$zone->name}"),
        );

        // Placeholders for the remaining offices. Real constituencies are imported in production.
        if (! app()->isProduction()) {
            $this->make($state, 'Bauchi Federal Constituency', ConstituencyType::HOUSE_OF_REPRESENTATIVES, 'Member, House of Representatives');
            $this->make($state, 'Bauchi State Assembly Constituency', ConstituencyType::STATE_ASSEMBLY, 'Member, State House of Assembly');
            $this->make($state, 'Bauchi LGA Chairmanship', ConstituencyType::CHAIRMANSHIP, 'Local government chairman');
            $this->make($state, 'Bauchi Councillorship', ConstituencyType::COUNCILLOR, 'Ward councillor');
        }
    }

    private function make(State $state, string $name, ConstituencyType $type, string $description): void
    {
        Constituency::firstOrCreate(
            ['name' => $name, 'type' => $type->value],
            ['state_id' => $state->id, 'description' => $description],
        );
    }
}