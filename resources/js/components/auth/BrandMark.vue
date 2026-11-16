<script setup lang="ts">
import logoUrl from '@/assets/logo.jpg'

withDefaults(defineProps<{ size?: 'sm' | 'md'; status?: boolean }>(), { size: 'md', status: false })
</script>

<template>
  <div class="relative flex-shrink-0">
    <div
      :class="['rounded-full flex items-center justify-center', size === 'md' ? 'w-11 h-11' : 'w-10 h-10']"
      style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 50%, #ef4444 100%); padding: 2px; box-shadow: 0 0 0 2px rgba(239,68,68,0.25), 0 4px 12px rgba(0,0,0,0.4);"
    >
      <div class="w-full h-full rounded-full overflow-hidden" style="background: linear-gradient(145deg, #171717, #000000);">
        <img :src="logoUrl" alt="PRP" class="w-full h-full rounded-full object-cover" />
      </div>
    </div>
    <div
      v-if="status"
      class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2"
      style="background: #ef4444; border-color: #171717;"
    />
  </div>
</template>