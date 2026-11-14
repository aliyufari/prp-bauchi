<script setup lang="ts">
/** Headline number with a 12-week sparkline and the recent change. */
import { computed } from 'vue'

const props = defineProps<{ label: string; value: number; delta?: number; deltaLabel?: string; series?: number[]; tone?: 'green' | 'gold' | 'slate' | 'teal' }>()
const colors = { green: '#15803d', gold: '#c9960a', slate: '#475569', teal: '#0d9488' }
const color = computed(() => colors[props.tone ?? 'green'])
const fmt = (n: number) => (Number.isFinite(Number(n)) ? Number(n) : 0).toLocaleString()

const W = 120, H = 36
const path = computed(() => {
  const s = (props.series ?? []).map((n) => Number(n) || 0)
  if (s.length < 2) return null
  const max = Math.max(1, ...s)
  const pts = s.map((v, i) => [(i / (s.length - 1)) * W, H - 3 - (v / max) * (H - 6)] as const)
  const line = pts.map(([x, y], i) => `${i ? 'L' : 'M'}${x.toFixed(1)} ${y.toFixed(1)}`).join(' ')
  return { line, area: `${line} L${W} ${H} L0 ${H} Z` }
})
</script>

<template>
  <div class="group relative min-w-0 overflow-hidden rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
    <div class="absolute inset-x-0 top-0 h-1" :style="{ background: color }" />
    <p class="truncate text-[10px] font-bold tracking-wider text-gray-500 uppercase">{{ label }}</p>
    <p class="mt-1 text-2xl font-black text-gray-900 sm:text-3xl">{{ fmt(value) }}</p>
    <p v-if="delta !== undefined" class="mt-0.5 text-[11px] font-semibold" :class="delta > 0 ? 'text-green-700' : 'text-gray-400'">{{ delta > 0 ? '▲ +' : '' }}{{ fmt(delta) }} {{ deltaLabel ?? 'in 30 days' }}</p>
    <svg v-if="path" :viewBox="`0 0 ${W} ${H}`" preserveAspectRatio="none" class="mt-3 h-9 w-full" aria-hidden="true">
      <path :d="path.area" :fill="color" fill-opacity="0.12" />
      <path :d="path.line" fill="none" :stroke="color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
    </svg>
  </div>
</template>
