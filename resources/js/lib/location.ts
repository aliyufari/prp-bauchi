import type { LocationLevel } from '@/types/location'

export const LEVEL_ORDER: LocationLevel[] = ['state', 'zone', 'lga', 'ward', 'pu']

export const LEVEL_LABEL: Record<LocationLevel, string> = {
    state: 'State',
    zone: 'Senatorial zone',
    lga: 'Local government area',
    ward: 'Ward',
    pu: 'Polling unit',
}

/** Short wording used in "Zonal", "LGA" style headings. */
export const LEVEL_SHORT: Record<LocationLevel, string> = {
    state: 'State',
    zone: 'Zonal',
    lga: 'LGA',
    ward: 'Ward',
    pu: 'Polling unit',
}

/**
 * Work out which location level a "type" option stands for, e.g. a coordinator
 * level of `zonal` / "Zonal Coordinator" -> `zone`. Matches on the option value
 * first, then its label, so it works with whatever the backend names its enums.
 * Returns null when the type does not describe a level (e.g. stakeholder categories).
 */
export function levelFromType(
    value: string | number | null | undefined,
    options: { value: string | number; label: string }[] = [],
): LocationLevel | null {
    if (value == null || value === '') return null
    const label = options.find((o) => String(o.value) === String(value))?.label ?? ''

    for (const text of [String(value), label]) {
        const t = text.toLowerCase()
        if (/polling|(^|[^a-z])pu([^a-z]|$)/.test(t)) return 'pu'
        if (/ward/.test(t)) return 'ward'
        if (/lga|local[\s_-]*gov/.test(t)) return 'lga'
        if (/zon|senator/.test(t)) return 'zone'
        if (/state/.test(t)) return 'state'
    }
    return null
}

/** Deepest level that has a value, used to restore the level when editing a record. */
export function deepestLevel(model: Partial<Record<`${LocationLevel}_id`, unknown>>): LocationLevel | null {
    for (const level of [...LEVEL_ORDER].reverse()) {
        const v = model[`${level}_id`]
        if (v !== null && v !== undefined && v !== '') return level
    }
    return null
}
