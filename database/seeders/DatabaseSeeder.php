<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            BauchiLocationsSeeder::class,
            ConstituencySeeder::class,
            SuperAdminSeeder::class,
            ElectionSeeder::class,
        ]);

        // if (! app()->isProduction()) {
        //     $this->call([
        //         DemoUserSeeder::class,
        //         CampaignTeamSeeder::class,
        //         ResultSeeder::class,
        //     ]);
        // }
    }
}