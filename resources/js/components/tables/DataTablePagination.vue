<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next'

interface Props {
  currentPage: number
  lastPage: number
  from: number | null
  to: number | null
  total: number
  perPage: number
  perPageOptions?: number[]
  disabled?: boolean
}
const props = withDefaults(defineProps<Props>(), {
  perPageOptions: () => [10, 15, 25, 50, 100],
  disabled: false,
})
const emit = defineEmits<{
  'update:currentPage': [page: number]
  'update:perPage': [perPage: number]
}>()

const safeLastPage = computed(() => Math.max(1, props.lastPage || 1))
const pages = computed(() => {
  const current = Math.min(Math.max(1, props.currentPage), safeLastPage.value)
  const last = safeLastPage.value
  const visible = new Set<number>([1, last])
  for (let page = Math.max(2, current - 1); page <= Math.min(last - 1, current + 1); page++) visible.add(page)
  const ordered = [...visible].sort((a, b) => a - b)
  const result: (number | 'ellipsis')[] = []
  ordered.forEach((page, index) => {
    if (index > 0 && page - ordered[index - 1] > 1) result.push('ellipsis')
    result.push(page)
  })
  return result
})
function go(page: number) {
  if (props.disabled) return
  emit('update:currentPage', Math.min(Math.max(1, page), safeLastPage.value))
}
</script>

<template>
  <nav class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between" aria-label="Table pagination">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 text-xs text-gray-500">
      <span>
        Showing <strong class="font-semibold text-gray-800">{{ from ?? 0 }}</strong>–<strong class="font-semibold text-gray-800">{{ to ?? 0 }}</strong>
        of <strong class="font-semibold text-gray-800">{{ total }}</strong>
      </span>
      <label class="inline-flex items-center gap-2">
        <span class="text-gray-500">Rows per page</span>
        <select :value="perPage" :disabled="disabled"
          class="min-h-8 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-600/15 disabled:cursor-not-allowed disabled:bg-gray-50"
          @change="emit('update:perPage', Number(($event.target as HTMLSelectElement).value))">
          <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
        </select>
      </label>
    </div>

    <div class="flex items-center gap-1 self-end sm:self-auto">
      <button type="button" aria-label="First page" :disabled="disabled || currentPage <= 1" @click="go(1)"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-green-50 hover:text-green-800 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent">
        <ChevronsLeft class="h-4 w-4" />
      </button>
      <button type="button" aria-label="Previous page" :disabled="disabled || currentPage <= 1" @click="go(currentPage - 1)"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-green-50 hover:text-green-800 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent">
        <ChevronLeft class="h-4 w-4" />
      </button>
      <template v-for="(page, index) in pages" :key="`${page}-${index}`">
        <span v-if="page === 'ellipsis'" class="px-1.5 text-xs text-gray-400" aria-hidden="true">…</span>
        <button v-else type="button" :aria-label="`Page ${page}`" :aria-current="page === currentPage ? 'page' : undefined"
          :disabled="disabled"
          class="h-8 min-w-8 rounded-lg px-2 text-xs font-semibold transition disabled:cursor-not-allowed"
          :class="page === currentPage ? 'bg-green-700 text-white shadow-sm' : 'text-gray-600 hover:bg-green-50 hover:text-green-800'"
          @click="go(page)">{{ page }}</button>
      </template>
      <button type="button" aria-label="Next page" :disabled="disabled || currentPage >= safeLastPage" @click="go(currentPage + 1)"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-green-50 hover:text-green-800 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent">
        <ChevronRight class="h-4 w-4" />
      </button>
      <button type="button" aria-label="Last page" :disabled="disabled || currentPage >= safeLastPage" @click="go(safeLastPage)"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-green-50 hover:text-green-800 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent">
        <ChevronsRight class="h-4 w-4" />
      </button>
    </div>
  </nav>
</template>
