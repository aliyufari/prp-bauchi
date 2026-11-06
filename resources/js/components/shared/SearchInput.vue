<script setup lang="ts">
import { Search, X } from 'lucide-vue-next'
withDefaults(defineProps<{ modelValue?: string; placeholder?: string; disabled?: boolean; ariaLabel?: string }>(), { modelValue: '', placeholder: 'Search…', disabled: false, ariaLabel: 'Search' })
const emit = defineEmits<{ 'update:modelValue': [value: string]; search: [value: string] }>()
</script>
<template>
  <div class="relative w-full">
    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9ca3af]" />
    <input :value="modelValue" type="search" :placeholder="placeholder" :aria-label="ariaLabel" :disabled="disabled"
      class="h-10 w-full rounded-xl border border-[#d1d5db] bg-white py-0 pl-9 pr-9 text-sm text-[#111827] outline-none placeholder:text-[#9ca3af] focus:border-[#16a34a] focus:ring-2 focus:ring-[#16a34a]/20 disabled:bg-[#f9fafb]"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      @keydown.enter="emit('search', modelValue)" />
    <button v-if="modelValue" type="button" aria-label="Clear search" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af] hover:text-[#374151]" @click="emit('update:modelValue', '')"><X class="h-4 w-4" /></button>
  </div>
</template>
