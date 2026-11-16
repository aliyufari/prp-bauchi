<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  firstName: string
  middleName?: string | null
  lastName: string
  email?: string | null
  subtitle?: string | null
  avatarUrl?: string | null
}
const props = defineProps<Props>()

const fullName = computed(() =>
  [props.firstName, props.middleName, props.lastName].filter(Boolean).join(' '),
)

const initials = computed(() =>
  `${props.firstName?.[0] ?? ''}${props.lastName?.[0] ?? ''}`.toUpperCase(),
)

// Deterministic accent color from the name, so the same person always gets the same tint.
const palette = [
  'bg-red-100 text-red-700',
  'bg-neutral-50 text-red-900',
  'bg-gray-100 text-gray-700',
  'bg-amber-100 text-amber-700',
  'bg-neutral-100 text-red-700',
  'bg-rose-100 text-rose-700',
]
const accent = computed(() => {
  const seed = fullName.value.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0)
  return palette[seed % palette.length]
})
</script>

<template>
  <div class="flex items-center gap-3">
    <img
      v-if="avatarUrl"
      :src="avatarUrl"
      :alt="fullName"
      class="h-9 w-9 shrink-0 rounded-full object-cover"
    />
    <div
      v-else
      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold"
      :class="accent"
    >
      {{ initials }}
    </div>
    <div class="min-w-0">
      <p class="truncate text-sm font-semibold text-gray-900">{{ fullName }}</p>
      <p class="truncate text-xs text-gray-500">{{ subtitle ?? email }}</p>
    </div>
  </div>
</template>