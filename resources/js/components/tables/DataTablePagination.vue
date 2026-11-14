<script setup lang="ts">
import { computed } from 'vue'
import { controlClasses } from '@/components/ui/form/controlClasses'
import { ChevronDown, ChevronLeft, ChevronRight } from 'lucide-vue-next'

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
// Same border / radius / focus ring as the form controls and buttons.
const focusRing = 'outline-none transition-all duration-150 focus-visible:ring-2 focus-visible:ring-green-600/30'
const navBtn = `inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-[#e5e7eb] bg-white text-sm font-semibold text-[#374151] hover:border-[#d1d5db] hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-[#e5e7eb] disabled:hover:bg-white ${focusRing}`
const pageBtn = `h-9 min-w-9 rounded-lg border px-2 text-sm font-semibold disabled:cursor-not-allowed ${focusRing}`
const pageIdle = 'border-transparent text-[#374151] hover:bg-green-50 hover:text-green-800'
const pageActive = 'border-green-700 bg-green-700 text-white'

function go(page: number) {
  if (props.disabled) return
  emit('update:currentPage', Math.min(Math.max(1, page), safeLastPage.value))
}
</script>

<template>
  <nav
    class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 sm:px-5 lg:flex-row lg:items-center lg:justify-between lg:gap-6"
    aria-label="Table pagination"
  >
    <!-- Summary + rows per page -->
    <div class="flex flex-col gap-2.5 text-xs text-gray-500 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-2">
      <p class="leading-5">
        Showing <span class="font-semibold text-gray-900">{{ from ?? 0 }}</span> to
        <span class="font-semibold text-gray-900">{{ to ?? 0 }}</span> of
        <span class="font-semibold text-gray-900">{{ total.toLocaleString() }}</span> results
      </p>
      <label class="inline-flex shrink-0 items-center gap-2.5 whitespace-nowrap">
        <span class="font-medium">Rows per page</span>
        <span class="relative inline-flex">
          <select
            :value="perPage"
            :disabled="disabled"
            aria-label="Rows per page"
            :class="[controlClasses(null, disabled), '!h-9 w-auto min-w-[4.5rem] cursor-pointer appearance-none !pr-8 !pl-3 text-sm font-semibold']"
            @change="emit('update:perPage', Number(($event.target as HTMLSelectElement).value))"
          >
            <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
          </select>
          <ChevronDown class="pointer-events-none absolute top-1/2 right-2.5 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
        </span>
      </label>
    </div>

    <!-- Pager -->
    <div class="flex items-center justify-between gap-2 lg:justify-end">
      <button type="button" aria-label="Previous page" :disabled="disabled || currentPage <= 1" :class="[navBtn, 'px-2.5 sm:px-3']" @click="go(currentPage - 1)">
        <ChevronLeft class="h-4 w-4" />
        <span class="hidden sm:inline">Previous</span>
      </button>

      <!-- Phones: compact "Page 2 of 12" -->
      <span class="text-xs font-medium text-gray-500 sm:hidden">
        Page <span class="font-semibold text-gray-900">{{ currentPage }}</span> of {{ safeLastPage }}
      </span>

      <!-- sm and up: numbered pages -->
      <div class="hidden items-center gap-1 sm:flex">
        <template v-for="(page, index) in pages" :key="`${page}-${index}`">
          <span v-if="page === 'ellipsis'" class="flex h-9 w-7 items-end justify-center pb-2 text-gray-400" aria-hidden="true">…</span>
          <button
            v-else
            type="button"
            :aria-label="`Page ${page}`"
            :aria-current="page === currentPage ? 'page' : undefined"
            :disabled="disabled"
            :class="[pageBtn, page === currentPage ? pageActive : pageIdle]"
            @click="go(page)"
          >
            {{ page }}
          </button>
        </template>
      </div>

      <button type="button" aria-label="Next page" :disabled="disabled || currentPage >= safeLastPage" :class="[navBtn, 'px-2.5 sm:px-3']" @click="go(currentPage + 1)">
        <span class="hidden sm:inline">Next</span>
        <ChevronRight class="h-4 w-4" />
      </button>
    </div>
  </nav>
</template>
