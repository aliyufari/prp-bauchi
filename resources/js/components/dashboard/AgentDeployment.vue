<script setup lang="ts">
import '@/plugins/echarts'
import { num, percentOf } from '@/lib/charts'
import { computed } from 'vue'
import VChart from 'vue-echarts'

const props = defineProps<{
  deployment: Partial<{
    total: number; deployed: number; deployedPct: number
    unassigned: number; unassignedPct: number
    awaitingTraining: number; awaitingTrainingPct: number
  }> | null
}>()

/* Counts are the source of truth; percentages are derived so the donut and the list can never disagree. */
const rows = computed(() => {
  const d = props.deployment ?? {}
  const deployed = num(d.deployed)
  const unassigned = num(d.unassigned)
  const awaiting = num(d.awaitingTraining)
  const total = num(d.total) || deployed + unassigned + awaiting
  return {
    total,
    items: [
      { key: 'deployed', name: 'Deployed', value: deployed, pct: percentOf(deployed, total), color: '#15803d', dot: 'bg-green-700' },
      { key: 'unassigned', name: 'Unassigned', value: unassigned, pct: percentOf(unassigned, total), color: '#f59e0b', dot: 'bg-amber-400' },
      { key: 'awaiting', name: 'Awaiting Training', value: awaiting, pct: percentOf(awaiting, total), color: '#ef4444', dot: 'bg-red-500' },
    ],
  }
})

const option = computed(() => ({
  aria: { enabled: true },
  tooltip: { trigger: 'item', confine: true, formatter: (p: any) => `${p.marker} ${p.name}: <b>${Number(p.value).toLocaleString()}</b> (${p.percent}%)` },
  series: [
    {
      type: 'pie',
      radius: ['58%', '78%'],
      avoidLabelOverlap: true,
      minAngle: 3,
      padAngle: 1,
      itemStyle: { borderRadius: 4, borderColor: '#fff', borderWidth: 1 },
      data: rows.value.items.filter((i) => i.value > 0).map((i) => ({ value: i.value, name: i.name, itemStyle: { color: i.color } })),
      label: { show: false },
      emphasis: { scaleSize: 4 },
    },
  ],
}))
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3">

    <!-- Header -->
    <div>
      <h3 class="text-sm font-bold tracking-tight text-gray-900">
        Agent Deployment Status
      </h3>
      <p class="text-xs text-gray-500 mt-1">
        Current field agent allocation
      </p>
    </div>

    <!-- Chart -->
    <div v-if="rows.total > 0" class="relative mx-auto h-[130px] w-[130px]">
      <VChart class="w-full h-full" :option="option" autoresize />

      <!-- Center Text -->
      <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
        <p class="text-sm font-black text-gray-900">
          {{ rows.total.toLocaleString() }}
        </p>
        <p class="text-[9px] text-gray-400 font-medium">
          Total Agents
        </p>
      </div>
    </div>

    <!-- Breakdown -->
    <ul v-if="rows.total > 0" class="space-y-1.5">
      <li v-for="item in rows.items" :key="item.key" class="flex items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-1.5">
          <span class="h-2 w-2 shrink-0 rounded-full" :class="item.dot" />
          <span class="truncate text-[10px] text-gray-600">{{ item.name }}</span>
        </div>
        <span class="shrink-0 text-[10px] font-bold">{{ item.value.toLocaleString() }} ({{ item.pct }}%)</span>
      </li>
    </ul>

    <div v-else class="rounded-xl bg-gray-50 px-4 py-7 text-center">
      <p class="text-sm font-semibold text-gray-700">No deployment data yet</p>
      <p class="mt-1 text-xs text-gray-500">Agent assignments and training status will appear here.</p>
    </div>

  </div>
</template>