<script setup lang="ts">
import { computed, ref } from 'vue'
const props = withDefaults(defineProps<{ src?: string | null; name?: string; size?: 'sm' | 'md' | 'lg' }>(), { name: 'User', size: 'md' })
const failed = ref(false)
const initials = computed(() => props.name.split(/\s+/).filter(Boolean).slice(0, 2).map(s => s[0]).join('').toUpperCase() || 'U')
</script>
<template>
  <span :class="['inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#fee2e2] font-semibold text-[#171717]', { 'h-8 w-8 text-xs': size === 'sm', 'h-10 w-10 text-sm': size === 'md', 'h-12 w-12 text-base': size === 'lg' }]">
    <img v-if="src && !failed" :src="src" :alt="name" class="h-full w-full object-cover" @error="failed = true" />
    <span v-else>{{ initials }}</span>
  </span>
</template>
