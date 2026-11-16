<script setup lang="ts">
import { MapPin, Clock } from 'lucide-vue-next'

defineProps<{
  activities: {
    date: { day: string; month: string }
    title: string
    location: string
    time: string
  }[]
}>()
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full flex flex-col">
    <div class="flex items-center justify-between mb-3">
      <div>
        <h3 class="text-sm font-bold tracking-tight text-gray-900">Upcoming Activities</h3>
        <p class="text-xs text-gray-500 mt-1">Scheduled events this period</p>
      </div>

    </div>

    <ul v-if="activities.length" class="flex-1 space-y-2 overflow-y-auto">
      <li
        v-for="a in activities"
        :key="a.title"
        class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-gray-50 transition-colors"
      >
        <!-- Date badge -->
        <div class="flex-shrink-0 w-9 bg-red-700 rounded-lg flex flex-col items-center justify-center py-1.5">
          <p class="text-[11px] font-black text-white leading-none">{{ a.date.day }}</p>
          <p class="text-[8px] font-bold text-red-200 uppercase tracking-wide mt-0.5">{{ a.date.month }}</p>
        </div>
        <!-- Info -->
        <div class="min-w-0 flex-1">
          <p class="text-[10px] font-bold text-gray-800 leading-tight truncate">{{ a.title }}</p>
          <div class="flex items-center gap-1 mt-0.5">
            <MapPin class="w-2.5 h-2.5 text-gray-400 flex-shrink-0" />
            <p class="text-[9px] text-gray-400 truncate">{{ a.location }}</p>
          </div>
          <div class="flex items-center gap-1 mt-0.5">
            <Clock class="w-2.5 h-2.5 text-gray-400 flex-shrink-0" />
            <p class="text-[9px] text-gray-400">{{ a.time }}</p>
          </div>
        </div>
      </li>
    </ul>

    <div v-else class="flex flex-1 flex-col items-center justify-center rounded-xl bg-gray-50 px-4 py-8 text-center">
      <Clock class="h-7 w-7 text-gray-300" />
      <p class="mt-3 text-sm font-semibold text-gray-700">Nothing scheduled</p>
      <p class="mt-1 text-xs text-gray-500">Upcoming campaign activities will appear here.</p>
    </div>
  </div>
</template>