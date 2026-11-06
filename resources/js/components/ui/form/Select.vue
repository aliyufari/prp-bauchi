<script setup lang="ts">
import { computed, useId } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import type { SelectOption, SelectValue } from './types'
import { controlClasses } from './controlClasses'
import FormField from './FormField.vue'

const props = withDefaults(defineProps<{
  modelValue?: SelectValue | null
  options: SelectOption[]
  label?: string
  hint?: string
  error?: string | string[] | null
  placeholder?: string
  id?: string
  name?: string
  required?: boolean
  disabled?: boolean
}>(), { placeholder: 'Select an option', required: false, disabled: false })
const emit = defineEmits<{ 'update:modelValue': [value: SelectValue | null] }>()
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)
function update(event: Event) {
  const raw = (event.target as HTMLSelectElement).value
  const option = props.options.find(item => String(item.value) === raw)
  emit('update:modelValue', option?.value ?? null)
}
</script>

<template>
  <FormField :id="fieldId" :label="label" :hint="hint" :error="error" :required="required">
    <template #default="{ id: resolvedId }">
      <div class="relative">
        <select
          :id="resolvedId" :name="name" :value="modelValue == null ? '' : String(modelValue)"
          :required="required" :disabled="disabled" :class="[controlClasses(error, disabled), 'h-10 appearance-none pr-9']"
          @change="update"
        >
          <option value="" disabled>{{ placeholder }}</option>
          <option v-for="option in options" :key="`${typeof option.value}:${option.value}`"
            :value="String(option.value)" :disabled="option.disabled">
            {{ option.label }}
          </option>
        </select>
        <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6b7280]" />
      </div>
    </template>
  </FormField>
</template>
