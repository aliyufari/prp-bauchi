<script setup lang="ts">
import { computed, useId } from 'vue'

const props = withDefaults(defineProps<{
  label?: string
  hint?: string
  error?: string | string[] | null
  required?: boolean
  id?: string
}>(), { required: false })

const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)
const errorMessage = computed(() => Array.isArray(props.error) ? props.error[0] : props.error)
</script>

<template>
  <div class="min-w-0 space-y-1.5">
    <label v-if="label" :for="fieldId" class="block text-sm font-semibold text-[#374151]">
      {{ label }}<span v-if="required" class="ml-1 text-[#dc2626]">*</span>
    </label>
    <slot :id="fieldId" />
    <p v-if="errorMessage" class="flex items-start gap-1 text-xs font-medium text-[#dc2626]" role="alert">
      <span aria-hidden="true">•</span>{{ errorMessage }}
    </p>
    <p v-else-if="hint" class="text-xs text-[#6b7280]">{{ hint }}</p>
  </div>
</template>
