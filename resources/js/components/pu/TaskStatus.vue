<script setup lang="ts">
import { CheckCircle2, Circle } from 'lucide-vue-next'

defineProps<{ done: boolean; doneText?: string; pendingText?: string }>()
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold"
    :class="done ? 'bg-neutral-900 text-white' : 'bg-red-50 text-red-700'"
  >
    <CheckCircle2 v-if="done" class="h-3.5 w-3.5" />
    <Circle v-else class="h-3.5 w-3.5" />
    <slot>{{ done ? (doneText ?? 'Done') : (pendingText ?? 'Pending') }}</slot>
  </span>
</template>
