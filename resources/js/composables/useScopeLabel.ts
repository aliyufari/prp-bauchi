import { computed } from 'vue'
import { usePermissions } from './usePermissions'

const LEVEL_WORD = { state: 'State', zone: 'Zonal', lga: 'LGA', ward: 'Ward', pu: 'Polling Unit' } as const
const PRIORITY = ['super_admin', 'admin', 'coordinator', 'supervisory_agent', 'returning_officer',
  'aspirant', 'stakeholder', 'party_agent', 'campaign_team_member', 'member']
const humanize = (s: string) => s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())

export function useScopeLabel() {
  const { auth } = usePermissions()

  const fullName = computed(() => `${auth.value.user?.first_name ?? ''} ${auth.value.user?.last_name ?? ''}`.trim())
  const initials = computed(() => `${auth.value.user?.first_name?.[0] ?? ''}${auth.value.user?.last_name?.[0] ?? ''}`.toUpperCase())

  const roleLabel = computed(() => {
    const roles = auth.value.roles ?? []
    const role = PRIORITY.find((r) => roles.includes(r)) ?? roles[0] ?? 'user'
    const level = auth.value.jurisdiction?.level
    return level && ['coordinator', 'supervisory_agent'].includes(role)
      ? `${LEVEL_WORD[level]} ${humanize(role)}`
      : humanize(role)
  })

  const scopeName = computed(() => auth.value.jurisdiction?.name ?? null)

  return { fullName, initials, roleLabel, scopeName }
}