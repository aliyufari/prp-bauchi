<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>
<template>
  <header class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
    <div class="min-w-0">
      <h1 class="text-xl font-bold tracking-tight text-[#0d3b26] sm:text-2xl">{{ title }}</h1>
      <p v-if="description" class="mt-1 text-sm text-[#6b7280]">{{ description }}</p>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2"><slot name="actions" /></div>
  </header>
</template>
