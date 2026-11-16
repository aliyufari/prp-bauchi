<script setup lang="ts">
import { computed } from 'vue'
import { AlertCircle, CheckCircle2, Info, TriangleAlert, X } from 'lucide-vue-next'
const props = withDefaults(defineProps<{ variant?: 'success' | 'info' | 'warning' | 'error'; title?: string; dismissible?: boolean }>(), { variant: 'info', dismissible: false })
const emit = defineEmits<{ dismiss: [] }>()
const icon = computed(() => ({ success: CheckCircle2, info: Info, warning: TriangleAlert, error: AlertCircle })[props.variant])
const styles = computed(() => ({
  success: 'border-[#fecaca] bg-[#fafafa] text-[#991b1b]',
  info: 'border-[#bfdbfe] bg-[#eff6ff] text-[#1e40af]',
  warning: 'border-[#fde68a] bg-[#fffbeb] text-[#92400e]',
  error: 'border-[#fecaca] bg-[#fef2f2] text-[#991b1b]',
})[props.variant])
</script>
<template>
  <div role="status" :class="['flex gap-3 rounded-xl border p-3.5 text-sm', styles]">
    <component :is="icon" class="mt-0.5 h-5 w-5 shrink-0" />
    <div class="min-w-0 flex-1">
      <p v-if="title" class="font-semibold">{{ title }}</p>
      <div :class="title && 'mt-1'"><slot /></div>
    </div>
    <button v-if="dismissible" type="button" aria-label="Dismiss alert" class="shrink-0 rounded p-1 opacity-70 hover:opacity-100" @click="emit('dismiss')"><X class="h-4 w-4" /></button>
  </div>
</template>
