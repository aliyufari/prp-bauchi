<script setup lang="ts">
import '@/plugins/echarts'
import { colorOr, num } from '@/lib/charts'
import { computed } from 'vue'
import VChart from 'vue-echarts'

const props = defineProps<{
  activities: { name: string; percentage: number; color?: string }[]
  totalActivities: number
}>()

/* Drops empty slices, coerces numbers and guarantees every slice has a colour. */
const slices = computed(() =>
  (props.activities ?? [])
    .map((a, i) => ({ name: String(a?.name ?? 'Unnamed'), value: num(a?.percentage), color: colorOr(a?.color, i) }))
    .filter((a) => a.value > 0),
)

const option = computed(() => ({
  aria: { enabled: true },
  tooltip: { trigger: 'item', confine: true, formatter: (p: any) => `${p.marker} ${p.name}: <b>${p.value}%</b>` },
  series: [
    {
      type: 'pie',
      radius: ['52%', '74%'],
      minAngle: 3,
      padAngle: 1,
      itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 1 },
      data: slices.value.map((s) => ({ value: s.value, name: s.name, itemStyle: { color: s.color } })),
      label: { show: false },
      emphasis: { scaleSize: 4 },
    },
  ],
}))
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full min-h-[300px] flex flex-col">

    <!-- Header -->
    <div class="mb-3">
      <h3 class="text-sm font-bold tracking-tight text-gray-900">
        Campaign Activities Overview
      </h3>
      <p class="text-xs text-gray-500 mt-1">
        Breakdown of ongoing campaign efforts
      </p>
    </div>

    <!-- Chart + Legend -->
    <div v-if="slices.length" class="flex flex-1 flex-wrap items-center justify-center gap-4">

      <!-- Chart -->
      <div class="relative h-[150px] w-[150px] shrink-0">
        <VChart class="w-full h-full" :option="option" autoresize />

        <!-- Center -->
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
          <p class="text-lg font-black text-gray-900">
            {{ totalActivities }}
          </p>
          <p class="text-[9px] text-gray-400 uppercase">Activities</p>
        </div>
      </div>

      <!-- Legend -->
      <ul class="min-w-[9rem] flex-1 basis-40 space-y-1.5">
        <li
          v-for="a in slices"
          :key="a.name"
          class="flex items-center justify-between gap-2"
        >
          <div class="flex items-center gap-2 min-w-0">
            <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: a.color }" />
            <span class="text-[10px] text-gray-600 truncate">{{ a.name }}</span>
          </div>
          <span class="text-[10px] font-bold">{{ a.value }}%</span>
        </li>
      </ul>

    </div>
    <div v-else class="flex flex-1 flex-col items-center justify-center rounded-xl bg-gray-50 px-4 py-8 text-center">
      <p class="text-3xl font-extrabold text-gray-300">{{ totalActivities }}</p>
      <p class="mt-2 text-sm font-semibold text-gray-700">No campaign breakdown yet</p>
      <p class="mt-1 text-xs text-gray-500">Activity categories will appear when data is available.</p>
    </div>
  </div>
</template>