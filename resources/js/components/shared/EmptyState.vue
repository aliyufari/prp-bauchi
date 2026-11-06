<script setup lang="ts">
import { Inbox } from 'lucide-vue-next'
withDefaults(defineProps<{ title?: string; description?: string }>(), { title: 'Nothing here yet', description: 'There are no records to display.' })
</script>
<template>
  <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-[#d1d5db] px-6 py-12 text-center">
    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#dcfce7] text-[#0d3b26]"><Inbox class="h-6 w-6" /></span>
    <h3 class="text-sm font-semibold text-[#111827]">{{ title }}</h3>
    <p class="mt-1 max-w-md text-sm text-[#6b7280]">{{ description }}</p>
    <div v-if="$slots.default" class="mt-4"><slot /></div>
  </div>
</template>
