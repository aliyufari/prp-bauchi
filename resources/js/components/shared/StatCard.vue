<script setup lang="ts">
import { computed } from 'vue'
const props = withDefaults(defineProps<{ label: string; value: string | number; detail?: string; trend?: string; trendUp?: boolean }>(), { trendUp: true })
const trendClass = computed(() => props.trendUp ? 'text-[#15803d]' : 'text-[#dc2626]')
</script>
<template>
  <section class="rounded-xl border border-[#e5e7eb] bg-white p-4 shadow-sm">
    <p class="text-xs font-medium uppercase tracking-wide text-[#6b7280]">{{ label }}</p>
    <div class="mt-2 flex items-end justify-between gap-3">
      <p class="text-2xl font-bold tracking-tight text-[#0d3b26]">{{ value }}</p>
      <span v-if="trend" :class="['text-xs font-semibold', trendClass]">{{ trend }}</span>
    </div>
    <p v-if="detail" class="mt-1 text-xs text-[#6b7280]">{{ detail }}</p>
    <div v-if="$slots.default" class="mt-3"><slot /></div>
  </section>
</template>
