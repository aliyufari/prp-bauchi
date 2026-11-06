<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FormattedArrayExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
        private readonly string $title = 'REPORT',
    ) {}

    public function headings(): array
    {
        return array_map(fn ($heading) => strtoupper((string) $heading), $this->headings);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $this->title) ?: 'REPORT', 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '166534']],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ],
        ];
    }
}
