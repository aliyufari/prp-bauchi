<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,   
            StateSeeder::class,
            ZoneSeeder::class,
            LgaSeeder::class,
            ConstituencySeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                WardSeeder::class,
                PuSeeder::class,
                DemoUserSeeder::class,
                CampaignTeamSeeder::class,
                ResultSeeder::class,
            ]);
        }
    }
}