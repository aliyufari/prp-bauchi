<script setup lang="ts">
import { computed, useAttrs, useId } from 'vue'
import { controlClasses } from './controlClasses'
import FormField from './FormField.vue'

defineOptions({ inheritAttrs: false })

const props = withDefaults(defineProps<{
  modelValue?: string | number | null
  label?: string
  hint?: string
  error?: string | string[] | null
  placeholder?: string
  type?: string
  id?: string
  name?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  autocomplete?: string
  modelModifiers?: { number?: boolean; trim?: boolean }
}>(), {
  type: 'text', required: false, disabled: false, readonly: false,
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const attrs = useAttrs()
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)

function update(event: Event) {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}
</script>

<template>
  <FormField :id="fieldId" :label="label" :hint="hint" :error="error" :required="required">
    <template #default="{ id: resolvedId }">
      <input
        :id="resolvedId"
        :name="name"
        :value="modelValue ?? ''"
        :type="type"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        :autocomplete="autocomplete"
        :aria-invalid="Boolean(error)"
        :class="[
          controlClasses(error, disabled),
          'h-10 rounded-lg',
          readonly && 'cursor-default bg-[#f9fafb]',
        ]"
        v-bind="attrs"
        @input="update"
      />
    </template>
  </FormField>
</template>
