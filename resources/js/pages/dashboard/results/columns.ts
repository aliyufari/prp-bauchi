import type { Column } from '@/components/tables/DataTable.vue'

export const columns: Column[] = [
  { key: 'pu', label: 'Polling Unit', slot: 'pu' },
  { key: 'office', label: 'Office', slot: 'office' },
  { key: 'location', label: 'Location', slot: 'location' },
  { key: 'votes', label: 'Valid Votes', sortable: true, sortKey: 'total_valid_votes', slot: 'votes' },
  { key: 'leading', label: 'Leading Party', slot: 'leading' },
  { key: 'status', label: 'Status', slot: 'status' },
  { key: 'actions', label: '', slot: 'actions', class: 'text-right' },
]