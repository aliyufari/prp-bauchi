import type { Column } from '@/components/tables/DataTable.vue'

export const columns: Column[] = [
  { key: 'team', label: 'Team', sortable: true, sortKey: 'name', slot: 'team' },
  { key: 'aspirant', label: 'Campaign', slot: 'aspirant' },
  { key: 'lead', label: 'Team Lead', slot: 'lead' },
  { key: 'members', label: 'Members', slot: 'members' },
  { key: 'status', label: 'Status', sortable: true, slot: 'status' },
  { key: 'actions', label: '', slot: 'actions', class: 'text-right' },
]