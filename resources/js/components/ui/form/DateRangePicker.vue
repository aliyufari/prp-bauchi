<script setup lang="ts">
import { computed } from 'vue'
import DatePicker from './DatePicker.vue'
const props = withDefaults(defineProps<{
  modelValue?: { start: string | null; end: string | null }
  label?: string
  hint?: string
  errors?: { start?: string; end?: string }
  disabled?: boolean
}>(), { modelValue: () => ({ start: null, end: null }), disabled: false })
const emit = defineEmits<{ 'update:modelValue': [value: { start: string | null; end: string | null }] }>()
const endMin = computed(() => props.modelValue.start || undefined)
function update(key: 'start' | 'end', value: string) {
  emit('update:modelValue', { ...props.modelValue, [key]: value || null })
}
</script>
<template>
  <div class="space-y-1.5">
    <p v-if="label" class="text-sm font-semibold text-[#374151]">{{ label }}</p>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <DatePicker :model-value="modelValue.start" label="From" :hint="hint" :error="errors?.start" :disabled="disabled" @update:model-value="update('start', $event)" />
      <DatePicker :model-value="modelValue.end" label="To" :min="endMin" :error="errors?.end" :disabled="disabled" @update:model-value="update('end', $event)" />
    </div>
  </div>
</template>
