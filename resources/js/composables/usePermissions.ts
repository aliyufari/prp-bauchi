import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { AuthPayload } from '@/types/auth'

export function usePermissions() {
  const auth = computed(() => usePage().props.auth as AuthPayload)
  const permissions = computed(() => new Set(auth.value?.permissions ?? []))
  const roles = computed(() => new Set(auth.value?.roles ?? []))

  return {
    auth,
    can: (p: string) => permissions.value.has(p),
    canAny: (...ps: string[]) => ps.some((p) => permissions.value.has(p)),
    isRole: (...rs: string[]) => rs.some((r) => roles.value.has(r)),
  }
}