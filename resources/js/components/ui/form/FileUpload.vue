<script setup lang="ts">
import { computed, ref } from 'vue'
import { FileUp, X } from 'lucide-vue-next'
import FormField from './FormField.vue'
const props = withDefaults(defineProps<{
  modelValue?: File | File[] | null
  label?: string
  hint?: string
  error?: string | string[] | null
  accept?: string
  multiple?: boolean
  disabled?: boolean
  required?: boolean
}>(), { multiple: false, disabled: false, required: false })
const emit = defineEmits<{ 'update:modelValue': [value: File | File[] | null] }>()
const input = ref<HTMLInputElement | null>(null)
const files = computed(() => !props.modelValue ? [] : Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue])
function changed(event: Event) {
  const selected = Array.from((event.target as HTMLInputElement).files ?? [])
  emit('update:modelValue', props.multiple ? selected : selected[0] ?? null)
}
function clear() {
  emit('update:modelValue', props.multiple ? [] : null)
  if (input.value) input.value.value = ''
}
</script>
<template>
  <FormField :label="label" :hint="hint" :error="error" :required="required">
    <template #default>
      <div class="rounded-xl border border-dashed border-[#9ca3af] bg-[#f9fafb] p-4">
        <input ref="input" type="file" class="sr-only" :accept="accept" :multiple="multiple" :disabled="disabled" :required="required" @change="changed" />
        <button type="button" :disabled="disabled" class="flex w-full flex-col items-center gap-2 text-center disabled:opacity-50" @click="input?.click()">
          <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#dcfce7] text-[#0d3b26]"><FileUp class="h-5 w-5" /></span>
          <span class="text-sm font-semibold text-[#0d3b26]">Choose {{ multiple ? 'files' : 'a file' }}</span>
          <span class="text-xs text-[#6b7280]">Click to browse your device</span>
        </button>
        <ul v-if="files.length" class="mt-3 space-y-2 border-t border-[#e5e7eb] pt-3">
          <li v-for="file in files" :key="`${file.name}-${file.size}`" class="flex items-center justify-between gap-2 text-xs text-[#374151]">
            <span class="min-w-0 truncate">{{ file.name }} · {{ (file.size / 1024).toFixed(1) }} KB</span>
            <button type="button" aria-label="Remove selected files" class="rounded p-1 text-[#6b7280] hover:text-[#dc2626]" @click="clear"><X class="h-4 w-4" /></button>
          </li>
        </ul>
      </div>
    </template>
  </FormField>
</template>
