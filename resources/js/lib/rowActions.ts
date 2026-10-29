import type { RowAction } from '@/components/tables/ActionDropdown.vue'
import type { RowAbilities } from '@/types/table'

/** Builds the dropdown from the server's per-row `can` flags. Empty array = no menu. */
export function rowActions(can?: Partial<RowAbilities>, extra: RowAction[] = []): RowAction[] {
  return [
    ...(can?.view ? [{ key: 'view', label: 'View details', icon: 'eye' } as RowAction] : []),
    ...(can?.update ? [{ key: 'edit', label: 'Edit', icon: 'edit' } as RowAction] : []),
    ...extra,
    ...(can?.delete ? [{ key: 'delete', label: 'Delete', icon: 'delete', danger: true } as RowAction] : []),
  ]
}