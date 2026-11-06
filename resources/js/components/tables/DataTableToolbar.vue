<script setup lang="ts">
import { computed } from 'vue'
import { Download, Upload, RefreshCw } from 'lucide-vue-next'
import SearchInput from '@/components/shared/SearchInput.vue'

interface Props {
  modelValue: string
  placeholder?: string
  showExport?: boolean
  showImport?: boolean
  showRefresh?: boolean
}
const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Search...',
  showExport: false,
  showImport: false,
  showRefresh: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  refresh: []
  export: [format: 'csv' | 'xlsx']
  import: []
}>()


const searchValue = computed({ get: () => props.modelValue, set: (value: string) => emit('update:modelValue', value) })
</script>

<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="w-full lg:max-w-xs">
      <SearchInput v-model="searchValue" :placeholder="placeholder" aria-label="Search records" />
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <slot name="filters" />

      <button
        v-if="showRefresh"
        type="button"
        @click="emit('refresh')"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50"
      >
        <RefreshCw class="h-3.5 w-3.5" />
        Refresh
      </button>

      <button
        v-if="showImport"
        type="button"
        @click="emit('import')"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50"
      >
        <Upload class="h-3.5 w-3.5" />
        Import
      </button>

      <button
        v-if="showExport"
        type="button"
        @click="emit('export', 'csv')"
        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50"
      >
        <Download class="h-3.5 w-3.5" />
        CSV
      </button>
      <button
        v-if="showExport"
        type="button"
        @click="emit('export', 'xlsx')"
        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50"
      >
        <Download class="h-3.5 w-3.5" />
        Excel
      </button>

      <slot name="actions" />
    </div>
  </div>
</template>