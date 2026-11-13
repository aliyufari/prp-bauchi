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
    <div v-if="label || $slots['label-aside']" class="flex flex-wrap items-center justify-between gap-1">
      <label v-if="label" :for="fieldId" class="block text-sm font-semibold text-[#374151]">
        {{ label }}<span v-if="required" class="ml-1 text-[#dc2626]">*</span>
      </label>
      <slot name="label-aside" />
    </div>
    <slot :id="fieldId" />
    <p v-if="errorMessage" class="mt-0.5 flex items-center gap-1 text-xs font-medium text-[#dc2626]" role="alert">
      <span class="inline-block h-1 w-1 shrink-0 rounded-full bg-[#dc2626]" aria-hidden="true" />{{ errorMessage }}
    </p>
    <p v-else-if="hint" class="text-xs text-[#6b7280]">{{ hint }}</p>
  </div>
</template>
