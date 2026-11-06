<script setup lang="ts">
import { computed } from 'vue'
import VChart from 'vue-echarts'

const props = defineProps<{
  deployment: {
    total: number
    deployed: number
    deployedPct: number
    unassigned: number
    unassignedPct: number
    awaitingTraining: number
    awaitingTrainingPct: number
  }
}>()

const option = computed(() => ({
  tooltip: {
    trigger: 'item'
  },
  series: [
    {
      type: 'pie',
      radius: ['55%', '75%'],
      data: [
        { value: props.deployment.deployedPct, name: 'Deployed' },
        { value: props.deployment.unassignedPct, name: 'Unassigned' },
        { value: props.deployment.awaitingTrainingPct, name: 'Awaiting Training' },
      ],
      label: { show: false },
      color: ['#15803d', '#f59e0b', '#ef4444'],
    }
  ]
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
    <div v-if="props.deployment.total > 0" class="relative mx-auto h-[120px] w-[120px]">
      <VChart class="w-full h-full" :option="option" autoresize />

      <!-- Center Text -->
      <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
        <p class="text-sm font-black text-gray-900">
          {{ props.deployment.total.toLocaleString() }}
        </p>
        <p class="text-[9px] text-gray-400 font-medium">
          Total Agents
        </p>
      </div>
    </div>

    <!-- Breakdown -->
    <ul v-if="props.deployment.total > 0" class="space-y-1.5">

      <li class="flex items-center justify-between">
        <div class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-green-700" />
          <span class="text-[10px] text-gray-600">Deployed</span>
        </div>
        <span class="text-[10px] font-bold">
          {{ props.deployment.deployed.toLocaleString() }} ({{ props.deployment.deployedPct }}%)
        </span>
      </li>

      <li class="flex items-center justify-between">
        <div class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-amber-400" />
          <span class="text-[10px] text-gray-600">Unassigned</span>
        </div>
        <span class="text-[10px] font-bold">
          {{ props.deployment.unassigned.toLocaleString() }} ({{ props.deployment.unassignedPct }}%)
        </span>
      </li>

      <li class="flex items-center justify-between">
        <div class="flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-red-500" />
          <span class="text-[10px] text-gray-600">Awaiting Training</span>
        </div>
        <span class="text-[10px] font-bold">
          {{ props.deployment.awaitingTraining.toLocaleString() }} ({{ props.deployment.awaitingTrainingPct }}%)
        </span>
      </li>

    </ul>

    <div v-else class="rounded-xl bg-gray-50 px-4 py-7 text-center">
      <p class="text-sm font-semibold text-gray-700">No deployment data yet</p>
      <p class="mt-1 text-xs text-gray-500">Agent assignments and training status will appear here.</p>
    </div>

  </div>
</template>