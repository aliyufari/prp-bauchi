<script setup lang="ts">
defineProps<{
  announcements: { title: string; date: string }[]
}>()
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3">
    <div>
      <h3 class="text-sm font-bold tracking-tight text-gray-900">Recent Announcements</h3>
      <p class="text-xs text-gray-500 mt-1">Latest party communications</p>
    </div>

    <ul v-if="announcements.length" class="flex-1 space-y-2">
      <li
        v-for="a in announcements"
        :key="a.title"
        class="flex items-start gap-2 p-2 rounded-lg hover:bg-gray-50 transition-colors cursor-default"
      >
        <span class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0 mt-1.5" />
        <div class="min-w-0">
          <p class="text-[10px] font-semibold text-gray-800 leading-snug">{{ a.title }}</p>
          <p class="text-[9px] text-gray-400 mt-0.5">{{ a.date }}</p>
        </div>
      </li>
    </ul>

    <div v-else class="rounded-xl bg-gray-50 px-4 py-7 text-center">
      <p class="text-sm font-semibold text-gray-700">No announcements yet</p>
      <p class="mt-1 text-xs text-gray-500">Party updates will appear here.</p>
    </div>
  </div>
</template>