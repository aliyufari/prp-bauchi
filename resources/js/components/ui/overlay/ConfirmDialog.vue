<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'
import { AlertTriangle, X } from 'lucide-vue-next'
const props = withDefaults(defineProps<{ open: boolean; title?: string; description?: string; confirmText?: string; cancelText?: string; loading?: boolean; variant?: 'danger' | 'primary' }>(), {
  title: 'Are you sure?', confirmText: 'Confirm', cancelText: 'Cancel', loading: false, variant: 'danger',
})
const emit = defineEmits<{ confirm: []; cancel: []; close: [] }>()
function onKey(event: KeyboardEvent) { if (event.key === 'Escape') emit('close') }
watch(() => props.open, value => {
  if (value) window.addEventListener('keydown', onKey)
  else window.removeEventListener('keydown', onKey)
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>
<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4" @click.self="emit('close')">
      <section role="dialog" aria-modal="true" :aria-label="title" class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
        <div class="flex items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="variant === 'danger' ? 'bg-[#fee2e2] text-[#b91c1c]' : 'bg-red-100 text-red-800'"><AlertTriangle class="h-5 w-5" /></span>
          <div class="min-w-0 flex-1">
            <h2 class="font-semibold text-[#111827]">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm leading-6 text-[#6b7280]">{{ description }}</p>
          </div>
          <button type="button" aria-label="Close dialog" class="rounded p-1 text-[#9ca3af] hover:bg-[#f3f4f6]" @click="emit('close')"><X class="h-4 w-4" /></button>
        </div>
        <div v-if="$slots.default" class="mt-4 text-sm text-[#374151]"><slot /></div>
        <div class="mt-6 flex justify-end gap-2">
          <button type="button" class="rounded-lg border border-[#d1d5db] px-4 py-2 text-sm font-medium text-[#374151] hover:bg-[#f9fafb]" @click="emit('cancel')">{{ cancelText }}</button>
          <button type="button" :disabled="loading" :class="['rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-50', variant === 'danger' ? 'bg-[#dc2626] hover:bg-[#b91c1c]' : 'bg-red-700 hover:bg-red-800']" @click="emit('confirm')">{{ loading ? 'Please wait…' : confirmText }}</button>
        </div>
      </section>
    </div>
  </Teleport>
</template>
