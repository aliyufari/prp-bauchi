<script setup lang="ts">
import { computed, useAttrs, useId } from 'vue'
import FormField from './FormField.vue'
defineOptions({ inheritAttrs: false })
const attrs = useAttrs()
const props = withDefaults(defineProps<{
  modelValue?: boolean
  label?: string
  hint?: string
  error?: string | string[] | null
  id?: string
  name?: string
  disabled?: boolean
  required?: boolean
}>(), { modelValue: false, disabled: false, required: false })
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)
</script>
<template>
  <FormField :id="fieldId" :hint="hint" :error="error">
    <template #default="{ id: resolvedId }">
      <label class="inline-flex cursor-pointer items-start gap-2.5 text-sm text-[#374151]" :class="disabled && 'cursor-not-allowed opacity-60'">
        <input :id="resolvedId" :name="name" type="checkbox" :checked="modelValue" :disabled="disabled" :required="required"
          v-bind="attrs"
          class="mt-0.5 h-4 w-4 rounded border-[#d1d5db] text-[#16a34a] accent-[#16a34a] focus:ring-2 focus:ring-[#16a34a]/30"
          @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)" />
        <span>{{ label }}<span v-if="required" class="ml-1 text-[#dc2626]">*</span></span>
      </label>
    </template>
  </FormField>
</template>
