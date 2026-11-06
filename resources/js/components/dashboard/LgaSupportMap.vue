<script setup lang="ts">
defineProps<{
  data: { lga: string; supportLevel: 'High' | 'Medium' | 'Low' }[]
}>()

const levelStyle: Record<string, string> = {
  High:   'bg-green-600 text-white',
  Medium: 'bg-green-200 text-green-800',
  Low:    'bg-gray-100 text-gray-500',
}
const legendStyle: Record<string, string> = {
  High:   'bg-green-600',
  Medium: 'bg-green-200',
  Low:    'bg-gray-200',
}
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full flex flex-col">
    <div class="flex items-center justify-between mb-3">
      <div>
        <h3 class="text-sm font-bold tracking-tight text-gray-900">Support by LGA (Heat Map)</h3>
        <p class="text-xs text-gray-500 mt-1">Ward-level support distribution</p>
      </div>
      <!-- Legend -->
      <div v-if="data.length" class="flex items-center gap-3">
        <div v-for="(cls, level) in legendStyle" :key="level" class="flex items-center gap-1">
          <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" :class="cls" />
          <span class="text-[9px] font-semibold text-gray-500">{{ level }}</span>
        </div>
      </div>
    </div>

    <!-- Grid of LGA badges -->
    <div v-if="data.length" class="flex-1 overflow-y-auto">
      <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
        <div
          v-for="d in data"
          :key="d.lga"
          :class="['rounded-lg px-2 py-1.5 text-center transition-transform hover:scale-105 cursor-default', levelStyle[d.supportLevel]]"
        >
          <p class="text-[9px] font-bold truncate leading-tight">{{ d.lga }}</p>
          <p class="text-[8px] opacity-75 mt-0.5">{{ d.supportLevel }}</p>
        </div>
      </div>
    </div>
    <div v-else class="flex flex-1 flex-col items-center justify-center rounded-xl bg-gray-50 px-4 py-8 text-center">
      <p class="text-sm font-semibold text-gray-700">Support insights are not available yet</p>
      <p class="mt-1 text-xs text-gray-500">LGA support levels will appear when campaign data is recorded.</p>
    </div>
  </div>
</template>