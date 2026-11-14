<script setup lang="ts">
import '@/plugins/echarts'
import { compact, escapeHtml, num } from '@/lib/charts'
import { Users } from 'lucide-vue-next'
import { computed } from 'vue'
import VChart from 'vue-echarts'

const props = defineProps<{
  data: { lga: string; count: number }[]
}>()

/* Clean rows only, biggest first, so the chart reads the same whatever order the API used. */
const rows = computed(() =>
  (props.data ?? [])
    .map((d) => ({ lga: String(d?.lga ?? '').trim(), count: num(d?.count) }))
    .filter((d) => d.lga)
    .sort((a, b) => b.count - a.count),
)
const total = computed(() => rows.value.reduce((sum, r) => sum + r.count, 0))
const dense = computed(() => rows.value.length > 8)

const option = computed(() => ({
  aria: { enabled: true },
  tooltip: {
    trigger: 'axis',
    axisPointer: { type: 'shadow' },
    confine: true,
    formatter: (params: any) => {
      const p = Array.isArray(params) ? params[0] : params
      return `${escapeHtml(String(p.name))}<br/>${p.marker} <b>${Number(p.value).toLocaleString()}</b> members`
    },
  },
  // containLabel keeps long LGA names inside the card instead of being clipped.
  grid: { left: 8, right: 8, top: 16, bottom: 8, containLabel: true },
  xAxis: {
    type: 'category',
    data: rows.value.map((r) => r.lga),
    axisLabel: {
      interval: 0,
      rotate: dense.value ? 45 : 0,
      fontSize: 10,
      color: '#6b7280',
      width: dense.value ? 80 : 64,
      overflow: 'truncate',
      hideOverlap: !dense.value,
    },
    axisLine: { lineStyle: { color: '#e5e7eb' } },
    axisTick: { show: false },
  },
  yAxis: {
    type: 'value',
    minInterval: 1, // member counts are whole numbers
    axisLabel: { fontSize: 10, color: '#9ca3af', formatter: (v: number) => compact(v) },
    splitLine: { lineStyle: { color: '#f0f0f0' } },
  },
  series: [
    {
      type: 'bar',
      data: rows.value.map((r) => r.count),
      itemStyle: { color: '#15803d', borderRadius: [4, 4, 0, 0] },
      emphasis: { itemStyle: { color: '#16a34a' } },
      barMaxWidth: 28,
      showBackground: true,
      backgroundStyle: { color: 'rgba(0,0,0,0.025)', borderRadius: [4, 4, 0, 0] },
    },
  ],
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

      <span class="rounded-full bg-green-50 px-3 py-1 text-[11px] font-semibold text-green-700">{{ total.toLocaleString() }} members</span>
    </div>

    <!-- Chart -->
    <div v-if="rows.length && total > 0" class="flex-1 min-h-0 h-[240px]">
      <VChart class="w-full h-full" :option="option" autoresize />
    </div>
    <div v-else class="flex flex-1 flex-col items-center justify-center rounded-xl bg-gray-50 px-4 py-8 text-center">
      <Users class="h-8 w-8 text-gray-300" />
      <p class="mt-3 text-sm font-semibold text-gray-700">No membership data yet</p>
      <p class="mt-1 text-xs text-gray-500">Member distribution by LGA will appear here.</p>
    </div>

  </div>
</template>