<script setup lang="ts">
import { computed, ref, useAttrs, useId, type Component } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'
import { controlClasses, controlReadonly } from './controlClasses'
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
  /** Optional leading icon (a lucide component). */
  icon?: Component
}>(), {
  type: 'text', required: false, disabled: false, readonly: false,
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const attrs = useAttrs()
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)

// Every password field gets a show / hide toggle.
const reveal = ref(false)
const isPassword = computed(() => props.type === 'password')
const inputType = computed(() => (isPassword.value && reveal.value ? 'text' : props.type))

function update(event: Event) {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}
</script>

<template>
  <FormField :id="fieldId" :label="label" :hint="hint" :error="error" :required="required">
    <template v-if="$slots['label-aside']" #label-aside><slot name="label-aside" /></template>
    <template #default="{ id: resolvedId }">
      <div class="relative">
        <component :is="icon" v-if="icon" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9ca3af]" aria-hidden="true" />
        <input
          :id="resolvedId"
          :name="name"
          :value="modelValue ?? ''"
          :type="inputType"
          :placeholder="placeholder"
          :required="required"
          :disabled="disabled"
          :readonly="readonly"
          :autocomplete="autocomplete"
          :aria-invalid="Boolean(error)"
          :class="[
            controlClasses(error, disabled),
            icon && 'pl-10',
            isPassword && 'pr-10',
            readonly && !disabled && controlReadonly,
          ]"
          v-bind="attrs"
          @input="update"
        />
        <button
          v-if="isPassword && !disabled"
          type="button"
          tabindex="-1"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af] transition-colors hover:text-[#374151]"
          :aria-label="reveal ? 'Hide password' : 'Show password'"
          @click="reveal = !reveal"
        >
          <EyeOff v-if="reveal" class="h-4 w-4" />
          <Eye v-else class="h-4 w-4" />
        </button>
      </div>
    </template>
  </FormField>
</template>
