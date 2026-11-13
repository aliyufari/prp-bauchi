<script setup lang="ts">
/**
 * Auth-screen field. It is only a thin wrapper around the shared <Input>, so the
 * login / register fields and every other input in the app are the same component.
 */
import type { Component } from 'vue'
import Input from '@/components/ui/form/Input.vue'

withDefaults(defineProps<{
  modelValue: string
  id: string
  label: string
  type?: string
  icon?: Component
  error?: string
  placeholder?: string
  autocomplete?: string
  tabindex?: number
  autofocus?: boolean
  readonly?: boolean
}>(), { type: 'text' })

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <Input
    :id="id"
    :model-value="modelValue"
    :label="label"
    :type="type"
    :icon="icon"
    :error="error"
    :placeholder="placeholder"
    :autocomplete="autocomplete"
    :tabindex="tabindex"
    :autofocus="autofocus"
    :readonly="readonly"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <template v-if="$slots['label-aside']" #label-aside><slot name="label-aside" /></template>
  </Input>
</template>
