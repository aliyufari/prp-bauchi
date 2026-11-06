<script setup lang="ts">
withDefaults(defineProps<{ modelValue?: boolean; label?: string; description?: string; disabled?: boolean }>(), { modelValue: false, disabled: false })
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()
</script>
<template>
  <button type="button" role="switch" :aria-checked="modelValue" :disabled="disabled"
    class="inline-flex items-center gap-3 text-left disabled:cursor-not-allowed disabled:opacity-50"
    @click="emit('update:modelValue', !modelValue)">
    <span class="relative h-6 w-11 shrink-0 rounded-full transition-colors" :class="modelValue ? 'bg-[#16a34a]' : 'bg-[#d1d5db]'">
      <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform" :class="modelValue && 'translate-x-5'" />
    </span>
    <span v-if="label || description" class="flex flex-col">
      <span v-if="label" class="text-sm font-medium text-[#374151]">{{ label }}</span>
      <span v-if="description" class="text-xs text-[#6b7280]">{{ description }}</span>
    </span>
  </button>
</template>
