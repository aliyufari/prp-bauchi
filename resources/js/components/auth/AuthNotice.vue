<script setup lang="ts">
import { CircleAlert, ShieldCheck } from 'lucide-vue-next'

withDefaults(defineProps<{ tone?: 'success' | 'error' }>(), { tone: 'success' })
</script>

<template>
  <div
    :class="[
      'mb-5 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium',
      tone === 'success' ? 'bg-[#fee2e2] border-[#fecaca] text-[#b91c1c]' : 'bg-[#fef2f2] border-[#fecaca] text-[#b91c1c]',
    ]"
  >
    <component :is="tone === 'success' ? ShieldCheck : CircleAlert" class="w-4 h-4 flex-shrink-0" />
    <slot />
  </div>
</template>