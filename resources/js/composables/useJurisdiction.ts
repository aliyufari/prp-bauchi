import { computed } from 'vue'
import { usePermissions } from './usePermissions'
import type { LocationKey } from '@/types/auth'

const ORDER: LocationKey[] = ['state', 'zone', 'lga', 'ward', 'pu']

export function useJurisdiction() {
  const { auth } = usePermissions()
  const j = computed(() => auth.value.jurisdiction)

  return {
    jurisdiction: j,
    level: computed(() => j.value.level),
    unrestricted: computed(() => j.value.unrestricted),
    isLocked: (key: LocationKey) => !!j.value.locked[key],
    lockedValue: (key: LocationKey) => j.value.locked[key]?.id ?? null,
    /** First level this user is allowed to choose freely (the one below their own). */
    firstFreeLevel: computed(() => {
      if (j.value.unrestricted || !j.value.level) return 'state' as LocationKey
      return ORDER[ORDER.indexOf(j.value.level) + 1] ?? null
    }),
  }
}