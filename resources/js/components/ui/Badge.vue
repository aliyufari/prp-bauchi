<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'green' | 'gold' | 'gray' | 'red' | 'blue'; pill?: boolean }>(), { variant: 'green', pill: true })
</script>
<template>
  <span :class="['inline-flex items-center px-2.5 py-1 text-xs font-semibold', pill ? 'rounded-full' : 'rounded-md', {
    'bg-[#dcfce7] text-[#0d3b26]': variant === 'green',
    'bg-[#fef3c7] text-[#92400e]': variant === 'gold',
    'bg-[#f3f4f6] text-[#4b5563]': variant === 'gray',
    'bg-[#fee2e2] text-[#991b1b]': variant === 'red',
    'bg-[#dbeafe] text-[#1e40af]': variant === 'blue',
  }]"><slot /></span>
</template>
