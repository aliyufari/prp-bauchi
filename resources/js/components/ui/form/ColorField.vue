<script setup lang="ts">
import { computed, useId } from 'vue'
import FormField from './FormField.vue'
const props = defineProps<{ modelValue?: string; label?: string; hint?: string; error?: string; disabled?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const id = useId()
const value = computed(() => props.modelValue || '#16a34a')
</script>
<template>
  <FormField :id="id" :label="label" :hint="hint" :error="error">
    <template #default="{ id: resolvedId }">
      <div class="flex items-center gap-3 rounded-lg border border-[#d1d5db] bg-white px-3 py-2">
        <input :id="resolvedId" type="color" :value="value" :disabled="disabled" class="h-8 w-10 cursor-pointer border-0 bg-transparent p-0 disabled:opacity-50" @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)" />
        <span class="font-mono text-sm uppercase text-[#374151]">{{ value }}</span>
      </div>
    </template>
  </FormField>
</template>
