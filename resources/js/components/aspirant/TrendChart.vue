<script setup lang="ts">
import '@/plugins/echarts'
import { escapeHtml, num } from '@/lib/charts'
import { TrendingUp } from 'lucide-vue-next'
import { computed } from 'vue'
import VChart from 'vue-echarts'

const props = defineProps<{ trend: { labels: string[]; members: number[]; agents: number[]; leaders: number[] } | null }>()

const series = computed(() => {
  const t = props.trend
  const labels = t?.labels ?? []
  const col = (a?: number[]) => labels.map((_, i) => num(a?.[i]))
  return { labels, members: col(t?.members), agents: col(t?.agents), leaders: col(t?.leaders) }
})
const total = computed(() => [...series.value.members, ...series.value.agents, ...series.value.leaders].reduce((a, b) => a + b, 0))

const line = (name: string, data: number[], color: string) => ({
  name, type: 'line', data, smooth: true, symbol: 'circle', symbolSize: 6, showSymbol: false,
  lineStyle: { width: 2.5, color }, itemStyle: { color },
  areaStyle: { color, opacity: 0.08 }, emphasis: { focus: 'series' },
})

const option = computed(() => ({
  aria: { enabled: true },
  color: ['#15803d', '#e8c44a', '#0d9488'],
  legend: { top: 0, right: 0, itemWidth: 10, itemHeight: 10, textStyle: { fontSize: 11, color: '#6b7280' } },
  tooltip: {
    trigger: 'axis', confine: true,
    formatter: (params: any[]) => `<b>Week of ${escapeHtml(String(params[0]?.axisValue ?? ''))}</b><br/>` + params.map((p) => `${p.marker} ${escapeHtml(p.seriesName)}: <b>${Number(p.value).toLocaleString()}</b>`).join('<br/>'),
  },
  grid: { left: 4, right: 8, top: 36, bottom: 4, containLabel: true },
  xAxis: { type: 'category', boundaryGap: false, data: series.value.labels, axisLabel: { fontSize: 10, color: '#6b7280', hideOverlap: true }, axisLine: { lineStyle: { color: '#e5e7eb' } }, axisTick: { show: false } },
  yAxis: { type: 'value', minInterval: 1, axisLabel: { fontSize: 10, color: '#9ca3af' }, splitLine: { lineStyle: { color: '#f0f0f0' } } },
  series: [line('Members', series.value.members, '#15803d'), line('Party agents', series.value.agents, '#e8c44a'), line('Leaders', series.value.leaders, '#0d9488')],
}))
</script>

<template>
  <div class="flex h-full min-h-[320px] flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="mb-3">
      <h3 class="text-sm font-bold tracking-tight text-gray-900">Growth momentum</h3>
      <p class="mt-1 text-xs text-gray-500">New sign-ups per week over the last 12 weeks</p>
    </div>
    <div v-if="total > 0" class="h-[250px] min-h-0 flex-1"><VChart class="h-full w-full" :option="option" autoresize /></div>
    <div v-else class="flex flex-1 flex-col items-center justify-center rounded-xl bg-gray-50 px-4 py-8 text-center">
      <TrendingUp class="h-8 w-8 text-gray-300" />
      <p class="mt-3 text-sm font-semibold text-gray-700">No sign-ups in the last 12 weeks</p>
      <p class="mt-1 text-xs text-gray-500">Weekly growth will chart here as people join.</p>
    </div>
  </div>
</template>
