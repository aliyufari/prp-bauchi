<script setup lang="ts">
withDefaults(defineProps<{ size?: 'sm' | 'md' | 'lg'; label?: string }>(), { size: 'md', label: 'Loading' })
</script>
<template>
  <span role="status" class="inline-flex items-center gap-2 text-sm text-[#6b7280]">
    <span :class="['inline-block animate-spin rounded-full border-2 border-[#fecaca] border-t-[#dc2626]', { 'h-4 w-4': size === 'sm', 'h-6 w-6': size === 'md', 'h-8 w-8': size === 'lg' }]" />
    <span class="sr-only">{{ label }}</span>
  </span>
</template>
