<script setup lang="ts">
import { computed } from 'vue'
import VChart from 'vue-echarts'
import { Users } from 'lucide-vue-next'

const props = defineProps<{
  data: { lga: string; count: number }[]
}>()

const option = computed(() => ({
  tooltip: {
    trigger: 'axis'
  },
  grid: {
    left: 10,
    right: 10,
    bottom: 60,
    top: 20
  },
  xAxis: {
    type: 'category',
    data: props.data.map(d => d.lga),
    axisLabel: {
      rotate: 45,
      fontSize: 8,
      color: '#9ca3af'
    },
    axisLine: { show: false },
    axisTick: { show: false }
  },
  yAxis: {
    type: 'value',
    axisLabel: {
      fontSize: 8,
      color: '#9ca3af',
      formatter: (v: number) => (v >= 1000 ? `${v / 1000}k` : v)
    },
    splitLine: {
      lineStyle: { color: '#f0f0f0' }
    }
  },
  series: [
    {
      type: 'bar',
      data: props.data.map(d => d.count),
      itemStyle: {
        color: '#15803d',
        borderRadius: [4, 4, 0, 0]
      },
      barMaxWidth: 20
    }
  ]
}))
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full min-h-[300px] flex flex-col">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-sm font-bold tracking-tight text-gray-900">
          Membership by LGA
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Member distribution across local governments
        </p>
      </div>

      <span class="rounded-full bg-green-50 px-3 py-1 text-[11px] font-semibold text-green-700">Bauchi State</span>
    </div>

    <!-- Chart -->
    <div v-if="data.length" class="flex-1 min-h-0 h-[240px]">
      <VChart class="w-full h-full" :option="option" autoresize />
    </div>
    <div v-else class="flex flex-1 flex-col items-center justify-center rounded-xl bg-gray-50 px-4 py-8 text-center">
      <Users class="h-8 w-8 text-gray-300" />
      <p class="mt-3 text-sm font-semibold text-gray-700">No membership data yet</p>
      <p class="mt-1 text-xs text-gray-500">Member distribution by LGA will appear here.</p>
    </div>

  </div>
</template>