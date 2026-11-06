<?php

namespace App\Imports;

use App\Models\Lga;
use App\Models\Pu;
use App\Models\State;
use App\Models\Ward;
use App\Models\Zone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BauchiLocationsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $stateName = $this->normalizeName($row['state'] ?? null);
            $zoneName  = $this->normalizeName($row['zone'] ?? null);
            $lgaName   = $this->normalizeName($row['lga'] ?? null);
            $wardName  = $this->normalizeName($row['ra'] ?? null);
            $puName    = $this->normalizeName($row['pu'] ?? null);
            $puCode    = $this->normalizeCode($row['delim'] ?? null);

            // Skip blank or incomplete rows.
            if (
                ! $stateName ||
                ! $zoneName ||
                ! $lgaName ||
                ! $wardName ||
                ! $puName ||
                ! $puCode
            ) {
                continue;
            }

            try {
                DB::transaction(function () use (
                    $stateName,
                    $zoneName,
                    $lgaName,
                    $wardName,
                    $puName,
                    $puCode
                ) {
                    // State
                    $state = State::firstOrCreate(
                        ['name' => $stateName],
                        ['id' => (string) Str::uuid()]
                    );

                    // Zone belongs to State
                    $zone = Zone::firstOrCreate(
                        [
                            'name' => $zoneName,
                            'state_id' => $state->id,
                        ],
                        ['id' => (string) Str::uuid()]
                    );

                    // LGA belongs to Zone
                    $lga = Lga::firstOrCreate(
                        [
                            'name' => $lgaName,
                            'zone_id' => $zone->id,
                        ],
                        ['id' => (string) Str::uuid()]
                    );

                    // Ward (RA) belongs to LGA
                    $ward = Ward::firstOrCreate(
                        [
                            'name' => $wardName,
                            'lga_id' => $lga->id,
                        ],
                        ['id' => (string) Str::uuid()]
                    );

                    // PU belongs to Ward.
                    // The code is kept as a string to preserve its format.
                    Pu::updateOrCreate(
                        [
                            'code' => $puCode,
                            'ward_id' => $ward->id,
                        ],
                        [
                            'name' => $puName,
                        ]
                    );
                });
            } catch (\Throwable $exception) {
                Log::error('Bauchi location import failed.', [
                    'row' => $index,
                    'pu_code' => $puCode,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        }
    }

    private function normalizeName(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Str::title(mb_strtolower($value));
    }

    private function normalizeCode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
