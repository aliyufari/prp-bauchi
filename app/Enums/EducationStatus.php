<?php

namespace App\Enums;

enum EducationStatus: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case NCE = 'nce';
    case ND = 'nd';
    case HND = 'hnd';
    case BSC = 'bsc';
    case MSC = 'msc';
    case PHD = 'phd';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PRIMARY => 'Primary School',
            self::SECONDARY => 'Secondary School',
            self::NCE => 'NCE',
            self::ND => 'ND',
            self::HND => 'HND',
            self::BSC => 'BSc',
            self::MSC => 'MSc',
            self::PHD => 'PhD',
            self::OTHER => 'Other',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
