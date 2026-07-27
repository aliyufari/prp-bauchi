import type { Column } from '@/components/tables/DataTable.vue'

export const columns: Column[] = [
  { key: 'pu', label: 'Polling Unit', sortable: true, sortKey: 'name', slot: 'pu' },
  { key: 'ward', label: 'Ward', slot: 'ward' },
  { key: 'voters', label: 'Registered Voters', sortable: true, sortKey: 'registered_voters', slot: 'voters' },
  { key: 'agents', label: 'Agents Deployed', slot: 'agents' },
  { key: 'actions', label: '', slot: 'actions', class: 'text-right' },
]