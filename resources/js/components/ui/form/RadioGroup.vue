<script setup lang="ts">
import { computed, useId } from 'vue'
import type { SelectOption, SelectValue } from './types'
import FormField from './FormField.vue'
const props = withDefaults(defineProps<{
  modelValue?: SelectValue | null
  options: SelectOption[]
  label?: string
  hint?: string
  error?: string | string[] | null
  name?: string
  direction?: 'vertical' | 'horizontal'
  disabled?: boolean
}>(), { direction: 'vertical', disabled: false })
const emit = defineEmits<{ 'update:modelValue': [value: SelectValue] }>()
const groupId = useId()
const name = computed(() => props.name || groupId)
</script>
<template>
  <FormField :label="label" :hint="hint" :error="error">
    <template #default>
      <div role="radiogroup" :class="['flex gap-3', direction === 'vertical' ? 'flex-col' : 'flex-wrap']">
        <label v-for="option in options" :key="String(option.value)" class="inline-flex items-center gap-2 text-sm text-[#374151]"
          :class="[(disabled || option.disabled) && 'cursor-not-allowed opacity-50', 'cursor-pointer']">
          <input type="radio" :name="name" :value="option.value" :checked="modelValue === option.value"
            :disabled="disabled || option.disabled" class="h-4 w-4 border-[#9ca3af] accent-[#dc2626] focus:ring-[#dc2626]/25"
            @change="emit('update:modelValue', option.value)" />
          {{ option.label }}
        </label>
      </div>
    </template>
  </FormField>
</template>
