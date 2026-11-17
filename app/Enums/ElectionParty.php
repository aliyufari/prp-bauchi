<?php

namespace App\Enums;

enum ElectionParty: string
{
    case ACCORD = 'A';
    case ACTION_ALLIANCE = 'AA';
    case ACTION_DEMOCRATIC_PARTY = 'ADP';
    case ACTION_PEOPLES_PARTY = 'APP';
    case AFRICAN_ACTION_CONGRESS = 'AAC';
    case ADC = 'ADC';
    case APC = 'APC';
    case APGA = 'APGA';
    case APM = 'APM';
    case BOOT_PARTY = 'BP';
    case DLA = 'DLA';
    case LP = 'LP';
    case NDP = 'NDP';
    case NRM = 'NRM';
    case NNPP = 'NNPP';
    case NDC = 'NDC';
    case PDP = 'PDP';
    case PRP = 'PRP';
    case SDP = 'SDP';
    case YPP = 'YPP';
    case YP = 'YP';
    case ZLP = 'ZLP';

    // Retained as a safe bucket for imported/legacy figures not mapped to a registered party.
    case OTHERS = 'OTHERS';

    public function label(): string
    {
        return $this->value;
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $party) => ['value' => $party->value, 'label' => $party->label()],
            self::cases(),
        );
    }
}
