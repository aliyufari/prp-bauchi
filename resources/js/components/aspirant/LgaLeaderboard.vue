<script setup lang="ts">
/** Strongest LGAs and the ones that still need attention, from the same scorecard data. */
import { computed } from 'vue'

const props = defineProps<{ rows: { id: string; name: string; people: number }[] }>()
const sorted = computed(() => [...(props.rows ?? [])].sort((a, b) => b.people - a.people))
const top = computed(() => sorted.value.slice(0, 5))
const low = computed(() => (sorted.value.length > 5 ? [...sorted.value].slice(-5).reverse() : []))
const max = computed(() => Math.max(1, ...(props.rows ?? []).map((r) => r.people)))
const w = (n: number) => `${Math.max(2, Math.min(100, (n / max.value) * 100))}%`
const fmt = (n: number) => (Number(n) || 0).toLocaleString()
</script>

<template>
  <div class="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <h3 class="text-sm font-bold tracking-tight text-gray-900">LGA performance</h3>
    <p class="mt-1 text-xs text-gray-500">Where support is strongest and where to invest next</p>

    <div v-if="!top.length" class="mt-4 flex flex-1 items-center justify-center rounded-xl bg-gray-50 p-6 text-center text-sm text-gray-500">No LGA data yet.</div>
    <template v-else>
      <p class="mt-4 mb-2 text-[10px] font-black tracking-wider text-green-700 uppercase">Strongest</p>
      <ul class="space-y-2.5">
        <li v-for="(r, i) in top" :key="r.id">
          <div class="mb-1 flex items-center justify-between gap-2 text-xs"><span class="flex min-w-0 items-center gap-2"><span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-100 text-[10px] font-black text-green-800">{{ i + 1 }}</span><span class="truncate font-semibold text-gray-800">{{ r.name }}</span></span><span class="font-bold text-gray-900 tabular-nums">{{ fmt(r.people) }}</span></div>
          <div class="h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-green-600" :style="{ width: w(r.people) }" /></div>
        </li>
      </ul>
      <template v-if="low.length">
        <p class="mt-5 mb-2 text-[10px] font-black tracking-wider text-amber-600 uppercase">Needs attention</p>
        <ul class="space-y-2.5">
          <li v-for="r in low" :key="r.id">
            <div class="mb-1 flex items-center justify-between gap-2 text-xs"><span class="truncate font-semibold text-gray-700">{{ r.name }}</span><span class="font-bold text-gray-900 tabular-nums">{{ fmt(r.people) }}</span></div>
            <div class="h-1.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-amber-400" :style="{ width: w(r.people) }" /></div>
          </li>
        </ul>
      </template>
    </template>
  </div>
</template>
