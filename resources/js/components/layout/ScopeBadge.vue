<script setup lang="ts">
import { computed } from 'vue'
import { MapPin } from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'

const { auth } = usePermissions()
const j = computed(() => auth.value.jurisdiction)
const unassigned = computed(() => !j.value?.unrestricted && !j.value?.name)
const text = computed(() => (j.value?.unrestricted ? 'All locations' : j.value?.name ?? 'No location assigned'))
</script>

<template>
  <div
    class="mt-1.5 inline-flex max-w-full items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
    :style="unassigned ? 'background: rgba(251,191,36,0.15); color:#fcd34d;' : 'background: rgba(34,197,94,0.15); color:#86efac;'"
  >
    <MapPin class="h-3 w-3 shrink-0" />
    <span class="truncate">{{ text }}</span>
  </div>
</template>