import { computed } from 'vue'
import type { LocationModel, LocationLevel } from '@/types/location'

const KEYS: LocationLevel[] = ['state', 'zone', 'lga', 'ward', 'pu']

/** Bridges the shared location cascade to an Inertia useForm object. */
export function useLocationModel(getForm: () => Record<string, any>) {
  return computed<LocationModel>({
    get: () => Object.fromEntries(KEYS.map((key) => [`${key}_id`, getForm()[`${key}_id`] ?? null])) as LocationModel,
    set: (value) => {
      const form = getForm()
      for (const key of KEYS) {
        const field = `${key}_id` as const
        if (field in value) form[field] = value[field] ?? null
      }
    },
  })
}
