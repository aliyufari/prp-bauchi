<?php

namespace Database\Seeders;

use App\Imports\BauchiLocationsImport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class BauchiLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/data/bauchi_zones.xlsx');

        if (! File::exists($filePath)) {
            $this->command?->error(
                "Bauchi locations Excel file was not found: {$filePath}"
            );

            return;
        }

        $this->command?->info('Importing Bauchi locations...');

        Excel::import(
            new BauchiLocationsImport(),
            $filePath
        );

        $this->command?->info(
            'Bauchi locations imported successfully.'
        );
    }
}
