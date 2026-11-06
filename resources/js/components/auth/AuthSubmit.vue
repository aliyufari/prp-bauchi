<script setup lang="ts">
import { Loader } from 'lucide-vue-next'

defineProps<{ processing: boolean; label: string; processingLabel: string; tabindex?: number }>()
</script>

<template>
  <button
    type="submit"
    :tabindex="tabindex"
    :disabled="processing"
    :class="[
      'mt-1 w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-bold text-white transition-all duration-150',
      'focus:outline-none focus:ring-2 focus:ring-[#16a34a]/40 focus:ring-offset-2',
      processing ? 'bg-[#16a34a]/60 cursor-not-allowed' : 'active:scale-[0.98] shadow-sm hover:shadow-md',
    ]"
    :style="!processing ? 'background: linear-gradient(90deg, #22c55e, #16a34a); box-shadow: 0 2px 8px rgba(34,197,94,0.25);' : ''"
  >
    <Loader v-if="processing" class="w-4 h-4 animate-spin" />
    {{ processing ? processingLabel : label }}
  </button>
</template>