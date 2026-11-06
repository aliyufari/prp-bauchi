<script setup lang="ts">
import Spinner from '../ui/Spinner.vue'
withDefaults(defineProps<{ label?: string }>(), { label: 'Loading data…' })
</script>
<template>
  <div class="flex min-h-36 flex-col items-center justify-center gap-3 text-sm text-[#6b7280]" role="status">
    <Spinner size="lg" />
    <span>{{ label }}</span>
  </div>
</template>
