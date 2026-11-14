<script setup lang="ts">
/** SVG progress ring with the three ingredients of the readiness index listed under it. */
import { computed } from 'vue'

const props = defineProps<{ score: number; parts: { label: string; value: number }[] }>()
const R = 54
const C = 2 * Math.PI * R
const score = computed(() => Math.min(100, Math.max(0, Math.round(Number(props.score) || 0))))
const offset = computed(() => C * (1 - score.value / 100))
const tone = computed(() => (score.value >= 70 ? 'Strong' : score.value >= 40 ? 'Building' : 'Early stage'))
const bar = (v: number) => `${Math.min(100, Math.max(0, Number(v) || 0))}%`
</script>

<template>
  <div class="w-full">
    <p class="text-center text-[10px] font-black tracking-[0.2em] text-white/50 uppercase">Campaign readiness index</p>
    <div class="relative mx-auto mt-3 h-40 w-40 sm:h-44 sm:w-44">
      <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" role="img" :aria-label="`Readiness ${score} percent`">
        <defs><linearGradient id="ring" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#4ade80" /><stop offset="100%" stop-color="#e8c44a" /></linearGradient></defs>
        <circle cx="60" cy="60" :r="R" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="10" />
        <circle cx="60" cy="60" :r="R" fill="none" stroke="url(#ring)" stroke-width="10" stroke-linecap="round" :stroke-dasharray="C" :stroke-dashoffset="offset" class="transition-all duration-1000" />
      </svg>
      <div class="absolute inset-0 flex flex-col items-center justify-center"><span class="text-4xl font-black">{{ score }}<span class="text-lg text-white/50">%</span></span><span class="text-[11px] font-bold text-[#e8c44a]">{{ tone }}</span></div>
    </div>
    <ul class="mt-4 space-y-2.5">
      <li v-for="p in parts" :key="p.label">
        <div class="mb-1 flex justify-between text-[11px] font-semibold text-white/70"><span>{{ p.label }}</span><span>{{ Math.round(p.value) }}%</span></div>
        <div class="h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-green-400 transition-all duration-700" :style="{ width: bar(p.value) }" /></div>
      </li>
    </ul>
  </div>
</template>
