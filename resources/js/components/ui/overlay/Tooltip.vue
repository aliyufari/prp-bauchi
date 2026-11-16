<script setup lang="ts">
withDefaults(defineProps<{ text: string; position?: 'top' | 'bottom' | 'left' | 'right' }>(), { position: 'top' })
const positions = { top: 'bottom-full left-1/2 mb-2 -translate-x-1/2', bottom: 'left-1/2 top-full mt-2 -translate-x-1/2', left: 'right-full top-1/2 mr-2 -translate-y-1/2', right: 'left-full top-1/2 ml-2 -translate-y-1/2' }
</script>
<template>
  <span class="group relative inline-flex">
    <slot />
    <span role="tooltip" :class="['pointer-events-none absolute z-50 w-max max-w-56 rounded-md bg-[#000000] px-2.5 py-1.5 text-xs font-medium text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 group-focus-within:opacity-100', positions[position]]">{{ text }}</span>
  </span>
</template>
