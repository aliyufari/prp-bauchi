<script setup lang="ts">
import { computed, useId } from 'vue'
import { controlClasses } from './controlClasses'
import FormField from './FormField.vue'
const props = withDefaults(defineProps<{
  modelValue?: string | null
  label?: string
  hint?: string
  error?: string | string[] | null
  id?: string
  name?: string
  min?: string
  max?: string
  required?: boolean
  disabled?: boolean
}>(), { required: false, disabled: false })
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)
</script>
<template>
  <FormField :id="fieldId" :label="label" :hint="hint" :error="error" :required="required">
    <template #default="{ id: resolvedId }">
      <input :id="resolvedId" :name="name" type="date" :value="modelValue ?? ''" :min="min" :max="max"
        :required="required" :disabled="disabled" :class="controlClasses(error, disabled)"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)" />
    </template>
  </FormField>
</template>
