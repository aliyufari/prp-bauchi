import { computed } from 'vue'
import type { LocationKey } from '@/types/auth'

const KEYS: LocationKey[] = ['state', 'zone', 'lga', 'ward', 'pu']

export function useLocationModel(getForm: () => Record<string, any>) {
  return computed({
    get: () => Object.fromEntries(KEYS.map((k) => [`${k}_id`, getForm()[`${k}_id`] ?? null])),
    set: (v: Record<string, string | null>) => {
      const form = getForm()
      for (const k of KEYS) if (`${k}_id` in v) form[`${k}_id`] = v[`${k}_id`]
    },
  })
}