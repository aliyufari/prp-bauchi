<script setup lang="ts">
import { computed } from 'vue'
import { Loader } from 'lucide-vue-next'
const props = withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  type?: 'button' | 'submit' | 'reset'
  loading?: boolean
  disabled?: boolean
  block?: boolean
}>(), { variant: 'primary', size: 'md', type: 'button', loading: false, disabled: false, block: false })
const classes = computed(() => {
  const variants = {
    primary: 'border border-[#0d3b26] bg-[#0d3b26] text-white hover:bg-[#092b1c] focus:ring-[#16a34a]/25',
    secondary: 'border border-[#e8c44a] bg-[#e8c44a] text-[#061a11] hover:bg-[#d9b63d] focus:ring-[#e8c44a]/30',
    outline: 'border border-[#0d3b26] bg-white text-[#0d3b26] hover:bg-[#f0fdf4] focus:ring-[#16a34a]/20',
    ghost: 'border border-transparent bg-transparent text-[#0d3b26] hover:bg-[#f0fdf4] focus:ring-[#16a34a]/20',
    danger: 'border border-[#dc2626] bg-[#dc2626] text-white hover:bg-[#b91c1c] focus:ring-[#dc2626]/20',
  }
  const sizes = { sm: 'h-9 px-3 text-xs', md: 'h-10 px-4 text-sm', lg: 'h-11 px-5 text-sm' }
  return ['inline-flex items-center justify-center gap-2 rounded-lg font-semibold shadow-sm outline-none transition focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50', variants[props.variant], sizes[props.size], props.block && 'w-full']
})
</script>
<template>
  <button :type="type" :disabled="disabled || loading" :class="classes">
    <Loader v-if="loading" class="h-4 w-4 animate-spin" />
    <slot />
  </button>
</template>
