<?php

namespace Database\Seeders;

use App\Models\Election;
use Illuminate\Database\Seeder;

class ElectionSeeder extends Seeder
{
    public function run(): void
    {
        // Preserve any elections already supplied by the project or deployment.
        // Only create a starter election when a fresh database has none.
        if (Election::query()->exists()) {
            return;
        }

        Election::create([
            'name' => '2027 General Election',
            'date' => null,
            'description' => 'Initial election record. Set the official election date when it is confirmed.',
            'is_active' => true,
        ]);
    }
}
