<?php

namespace App\Console\Commands;

use App\Models\Result;
use App\Models\UserProfile;
use Illuminate\Console\Command;

class BackfillLocationChains extends Command
{
    protected $signature = 'locations:backfill';

    protected $description = 'Fill missing parent location ids on profiles and results';

    public function handle(): int
    {
        foreach ([UserProfile::class, Result::class] as $model) {
            $fixed = 0;

            $model::query()->chunkById(200, function ($rows) use (&$fixed) {
                foreach ($rows as $row) {
                    $row->save(); // observer normalises
                    $fixed += $row->wasChanged() ? 1 : 0;
                }
            });

            $this->info(class_basename($model) . ": {$fixed} rows updated");
        }

        return self::SUCCESS;
    }
}