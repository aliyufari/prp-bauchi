<script setup lang="ts">
/**
 * Profile photo picker: a round avatar preview with Choose / Change / Remove
 * actions, so it reads as "pick an avatar" and not as a generic file upload.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Camera, Trash2, UserRound } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import FormField from './FormField.vue'

const props = withDefaults(defineProps<{
  modelValue?: File | null
  label?: string
  hint?: string
  error?: string | string[] | null
  accept?: string
  /** Existing photo shown until a new one is chosen (edit mode). */
  currentUrl?: string | null
  maxSizeMb?: number
  disabled?: boolean
}>(), {
  label: 'Profile photo',
  hint: 'JPG, PNG or WebP · maximum 2 MB',
  accept: 'image/jpeg,image/png,image/webp',
  maxSizeMb: 2,
  disabled: false,
})
const emit = defineEmits<{ 'update:modelValue': [value: File | null] }>()

const input = ref<HTMLInputElement | null>(null)
const preview = ref<string | null>(null)
const localError = ref('')

const shown = computed(() => preview.value ?? props.currentUrl ?? null)
const hasPhoto = computed(() => Boolean(shown.value))
const message = computed(() => localError.value || props.error || null)

watch(() => props.modelValue, (file) => {
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = file ? URL.createObjectURL(file) : null
}, { immediate: true })
onBeforeUnmount(() => { if (preview.value) URL.revokeObjectURL(preview.value) })

function pick() {
  if (!props.disabled) input.value?.click()
}
function changed(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0] ?? null
  localError.value = ''
  if (file && file.size > props.maxSizeMb * 1024 * 1024) {
    localError.value = `Photo is too large. Maximum size is ${props.maxSizeMb} MB.`
    if (input.value) input.value.value = ''
    return
  }
  emit('update:modelValue', file)
}
function remove() {
  localError.value = ''
  emit('update:modelValue', null)
  if (input.value) input.value.value = ''
}
</script>

<template>
  <FormField :label="label" :error="message">
    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
      <button
        type="button"
        :disabled="disabled"
        :aria-label="hasPhoto ? 'Change profile photo' : 'Choose profile photo'"
        class="group relative h-20 w-20 shrink-0 rounded-full outline-none focus-visible:ring-2 focus-visible:ring-green-600/30 focus-visible:ring-offset-2 disabled:opacity-60"
        @click="pick"
      >
        <span
          :class="[
            'flex h-full w-full items-center justify-center overflow-hidden rounded-full bg-green-50 transition',
            hasPhoto ? 'ring-2 ring-white shadow-sm outline outline-1 outline-[#e5e7eb]' : 'border-2 border-dashed border-[#d1d5db] group-hover:border-green-600',
            message && !hasPhoto && 'border-[#dc2626]',
          ]"
        >
          <img v-if="shown" :src="shown" alt="Selected profile photo" class="h-full w-full object-cover" />
          <UserRound v-else class="h-9 w-9 text-green-700/40" aria-hidden="true" />
        </span>
        <span class="absolute -bottom-0.5 -right-0.5 flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-green-700 text-white transition group-hover:bg-green-800">
          <Camera class="h-3.5 w-3.5" aria-hidden="true" />
        </span>
      </button>

      <div class="flex min-w-0 flex-col items-center gap-2 text-center sm:items-start sm:text-left">
        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
          <Button variant="neutral" size="sm" :disabled="disabled" @click="pick">
            <Camera class="h-3.5 w-3.5" />
            {{ hasPhoto ? 'Change photo' : 'Choose photo' }}
          </Button>
          <Button v-if="modelValue" variant="ghost" size="sm" class="!text-[#dc2626] hover:!bg-[#fef2f2]" :disabled="disabled" @click="remove">
            <Trash2 class="h-3.5 w-3.5" />
            Remove
          </Button>
        </div>
        <p class="text-xs text-[#6b7280]">{{ hint }}</p>
      </div>
      <input ref="input" type="file" class="sr-only" tabindex="-1" :accept="accept" :disabled="disabled" @change="changed" />
    </div>
  </FormField>
</template>
