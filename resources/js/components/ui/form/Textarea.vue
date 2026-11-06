<script setup lang="ts">
import { computed, useAttrs, useId } from 'vue'
import { controlClasses } from './controlClasses'
import FormField from './FormField.vue'

defineOptions({ inheritAttrs: false })
const props = withDefaults(defineProps<{
  modelValue?: string | null
  label?: string
  hint?: string
  error?: string | string[] | null
  placeholder?: string
  id?: string
  name?: string
  rows?: number
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  resize?: 'none' | 'vertical' | 'both'
}>(), { rows: 4, required: false, disabled: false, readonly: false, resize: 'vertical' })
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const attrs = useAttrs()
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)
</script>

<template>
  <FormField :id="fieldId" :label="label" :hint="hint" :error="error" :required="required">
    <template #default="{ id: resolvedId }">
      <textarea
        :id="resolvedId" :name="name" :value="modelValue ?? ''" :rows="rows"
        :placeholder="placeholder" :required="required" :disabled="disabled"
        :readonly="readonly" :class="[controlClasses(error, disabled), 'min-h-24', `resize-${resize}`]"
        v-bind="attrs" @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
      />
    </template>
  </FormField>
</template>
