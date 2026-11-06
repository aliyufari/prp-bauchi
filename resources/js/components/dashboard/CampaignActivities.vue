<script setup lang="ts">
import { computed } from 'vue'
import VChart from 'vue-echarts'

const props = defineProps<{
  activities: { name: string; percentage: number; color: string }[]
  totalActivities: number
}>()

const option = computed(() => ({
  tooltip: {
    trigger: 'item'
  },
  series: [
    {
      type: 'pie',
      radius: ['45%', '68%'],
      data: props.activities.map(a => ({
        value: a.percentage,
        name: a.name,
        itemStyle: { color: a.color }
      })),
      label: { show: false }
    }
  ]
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
    <div v-if="activities.length" class="flex flex-col items-center gap-4 flex-1 sm:flex-row">

      <!-- Chart -->
      <div class="relative w-[150px] h-[150px]">
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
      <ul class="flex-1 space-y-1.5 min-w-0">
        <li
          v-for="a in activities"
          :key="a.name"
          class="flex items-center justify-between"
        >
          <div class="flex items-center gap-2 min-w-0">
            <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: a.color }" />
            <span class="text-[10px] text-gray-600 truncate">{{ a.name }}</span>
          </div>
          <span class="text-[10px] font-bold">{{ a.percentage }}%</span>
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