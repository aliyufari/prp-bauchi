<script setup lang="ts">
type Status = 'Ongoing' | 'Planned' | 'Completed'

defineProps<{
  activities: {
    name?: string
    description?: string
    status?: Status
    icon?: string
  }[]
}>()

const statusStyle: Record<Status, string> = {
  Ongoing: 'bg-green-50 text-green-700',
  Planned: 'bg-amber-50 text-amber-700',
  Completed: 'bg-blue-50 text-green-900',
}

const getInitials = (name?: string) =>
  (name ?? 'NA')
    .split(' ')
    .map(n => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full flex flex-col overflow-hidden">

    <!-- Header -->
    <div class="mb-3">
      <h3 class="text-[10px] sm:text-sm font-bold tracking-tight text-gray-900">
        Election Victory Strategy
      </h3>
      <p class="text-xs text-gray-500 mt-1">
        Key strategic activities underway
      </p>
    </div>

    <!-- List -->
    <ul v-if="activities.length" class="flex-1 space-y-2 overflow-y-auto min-h-0">

      <li
        v-for="(a, index) in activities"
        :key="a.name || index"
        class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-gray-50 transition-colors"
      >

        <!-- Avatar -->
        <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <span class="text-[9px] font-black text-green-700">
            {{ getInitials(a.name) }}
          </span>
        </div>

        <!-- Content -->
        <div class="min-w-0 flex-1">

          <div class="flex items-start justify-between gap-2">

            <p class="text-[10px] sm:text-xs font-bold text-gray-800 leading-tight truncate">
              {{ a.name || 'Untitled Activity' }}
            </p>

            <span
              v-if="a.status"
              :class="[
                'text-[8px] font-bold px-1.5 py-0.5 rounded-full flex-shrink-0 whitespace-nowrap',
                statusStyle[a.status]
              ]"
            >
              {{ a.status }}
            </span>

          </div>

          <p class="text-[9px] sm:text-[10px] text-gray-400 mt-0.5 leading-snug line-clamp-2">
            {{ a.description || 'No description available' }}
          </p>

        </div>

      </li>

    </ul>
    <div v-if="!activities.length" class="flex flex-1 flex-col items-center justify-center rounded-xl bg-gray-50 px-4 py-8 text-center">
      <p class="text-sm font-semibold text-gray-700">No strategy activities yet</p>
      <p class="mt-1 text-xs text-gray-500">Approved campaign priorities will appear here.</p>
    </div>

  </div>
</template>