<script setup lang="ts">
defineProps<{
  supporters: { name: string; lga: string; amount: number; avatar?: string }[]
}>()

const initials = (name: string) =>
  name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()

const avatarColors = [
  'bg-red-100 text-red-700',
  'bg-neutral-50 text-red-900',
  'bg-gray-100 text-gray-700',
  'bg-amber-100 text-amber-700',
  'bg-red-100 text-red-700',
]
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3">
    <div>
      <h3 class="text-sm font-bold tracking-tight text-gray-900">Recent Supporters</h3>
      <p class="text-xs text-gray-500 mt-1">Latest financial contributors</p>
    </div>

    <ul v-if="supporters.length" class="flex-1 space-y-2">
      <li
        v-for="(s, i) in supporters"
        :key="s.name"
        class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-gray-50 transition-colors"
      >
        <!-- Avatar -->
        <div
          v-if="s.avatar"
          class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0"
        >
          <img :src="s.avatar" :alt="s.name" class="w-full h-full object-cover" />
        </div>
        <div
          v-else
          :class="['w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-[10px] font-black', avatarColors[i % avatarColors.length]]"
        >
          {{ initials(s.name) }}
        </div>

        <!-- Info -->
        <div class="min-w-0 flex-1">
          <p class="text-[10px] font-bold text-gray-800 truncate leading-tight">{{ s.name }}</p>
          <p class="text-[9px] text-gray-400 truncate">{{ s.lga }}</p>
        </div>

        <!-- Amount -->
        <p class="text-[10px] font-black text-red-700 flex-shrink-0">
          ₦{{ s.amount.toLocaleString() }}
        </p>
      </li>
    </ul>

    <div v-else class="rounded-xl bg-gray-50 px-4 py-7 text-center">
      <p class="text-sm font-semibold text-gray-700">No supporters recorded yet</p>
      <p class="mt-1 text-xs text-gray-500">Recent campaign supporters will appear here.</p>
    </div>
  </div>
</template>