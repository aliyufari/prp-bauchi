<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
const props = withDefaults(defineProps<{ label?: string; align?: 'left' | 'right'; disabled?: boolean }>(), { label: 'Options', align: 'right', disabled: false })
const root = ref<HTMLElement | null>(null)
const open = ref(false)
function handleOutsideClick(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) open.value = false
}
onMounted(() => document.addEventListener('mousedown', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleOutsideClick))
</script>
<template>
  <div ref="root" class="relative inline-block">
    <button type="button" :disabled="disabled" class="inline-flex items-center gap-2 rounded-lg border border-[#d1d5db] bg-white px-3 py-2 text-sm font-medium text-[#374151] hover:bg-[#f9fafb] disabled:opacity-50" @click="open = !open">
      {{ label }}<ChevronDown class="h-4 w-4" />
    </button>
    <div v-if="open" :class="['absolute z-50 mt-1 min-w-44 rounded-lg border border-[#e5e7eb] bg-white p-1 shadow-xl', align === 'right' ? 'right-0' : 'left-0']" @click="open = false">
      <slot />
    </div>
  </div>
</template>
