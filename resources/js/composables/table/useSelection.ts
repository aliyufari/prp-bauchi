import { computed, ref } from 'vue'

export type TableRowId = string | number

export function useSelection() {
  const selected = ref<TableRowId[]>([])
  const hasSelection = computed(() => selected.value.length > 0)

  function toggleSelection(id: TableRowId) {
    selected.value = selected.value.includes(id)
      ? selected.value.filter(item => item !== id)
      : [...selected.value, id]
  }

  /** Select or clear only the rows currently supplied by the server page. */
  function togglePage(ids: TableRowId[]) {
    const allSelected = ids.length > 0 && ids.every(id => selected.value.includes(id))
    selected.value = allSelected
      ? selected.value.filter(id => !ids.includes(id))
      : [...new Set([...selected.value, ...ids])]
  }

  function clearSelection() {
    selected.value = []
  }

  return { selected, hasSelection, toggleSelection, togglePage, clearSelection }
}
