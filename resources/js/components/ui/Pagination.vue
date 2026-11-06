<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
const props = withDefaults(defineProps<{ currentPage: number; lastPage: number; total?: number; perPage?: number; from?: number | null; to?: number | null; disabled?: boolean }>(), { total: 0, perPage: 15, from: null, to: null, disabled: false })
const emit = defineEmits<{ 'update:currentPage': [page: number]; change: [page: number] }>()
const pages = computed(() => {
  const start = Math.max(1, props.currentPage - 2)
  const end = Math.min(props.lastPage, start + 4)
  return Array.from({ length: Math.max(0, end - start + 1) }, (_, i) => start + i)
})
function go(page: number) {
  if (props.disabled || page < 1 || page > props.lastPage || page === props.currentPage) return
  emit('update:currentPage', page); emit('change', page)
}
</script>
<template>
  <nav v-if="lastPage > 1" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3">
    <p class="text-xs text-[#6b7280]">
      <template v-if="from !== null && to !== null">Showing {{ from }}–{{ to }} of {{ total }}</template>
      <template v-else>Page {{ currentPage }} of {{ lastPage }}</template>
    </p>
    <div class="flex items-center gap-1">
      <button type="button" aria-label="Previous page" :disabled="disabled || currentPage <= 1" class="rounded-lg border border-[#d1d5db] p-2 text-[#374151] hover:bg-[#f0fdf4] disabled:opacity-40" @click="go(currentPage - 1)"><ChevronLeft class="h-4 w-4" /></button>
      <button v-for="page in pages" :key="page" type="button" :aria-current="page === currentPage ? 'page' : undefined"
        :class="['min-w-9 rounded-lg border px-3 py-2 text-xs font-semibold', page === currentPage ? 'border-[#0d3b26] bg-[#0d3b26] text-white' : 'border-[#d1d5db] text-[#374151] hover:bg-[#f0fdf4]']"
        :disabled="disabled" @click="go(page)">{{ page }}</button>
      <button type="button" aria-label="Next page" :disabled="disabled || currentPage >= lastPage" class="rounded-lg border border-[#d1d5db] p-2 text-[#374151] hover:bg-[#f0fdf4] disabled:opacity-40" @click="go(currentPage + 1)"><ChevronRight class="h-4 w-4" /></button>
    </div>
  </nav>
</template>
