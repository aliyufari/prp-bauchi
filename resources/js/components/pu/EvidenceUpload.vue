<script setup lang="ts">
/**
 * Capture or pick media from the phone. "Record / Take photo" opens the camera
 * (capture attribute); "Choose" opens the gallery. Shows live previews.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Camera, ImagePlus, Video, X } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
  modelValue: File[]
  kind: 'video' | 'image'
  multiple?: boolean
  maxMb: number
  maxFiles?: number
  error?: string | null
  disabled?: boolean
}>(), { multiple: false, maxFiles: 1, error: null, disabled: false })

const emit = defineEmits<{ 'update:modelValue': [files: File[]]; reject: [message: string] }>()

const cameraInput = ref<HTMLInputElement | null>(null)
const galleryInput = ref<HTMLInputElement | null>(null)
const accept = computed(() => (props.kind === 'video' ? 'video/*' : 'image/*'))

const previews = ref<{ file: File; url: string }[]>([])
watch(() => props.modelValue, (files) => {
  previews.value.forEach((p) => URL.revokeObjectURL(p.url))
  previews.value = files.map((file) => ({ file, url: URL.createObjectURL(file) }))
}, { immediate: true })
onBeforeUnmount(() => previews.value.forEach((p) => URL.revokeObjectURL(p.url)))

function add(event: Event) {
  const input = event.target as HTMLInputElement
  const picked = Array.from(input.files ?? [])
  input.value = ''

  const ok: File[] = []
  for (const f of picked) {
    if (f.size > props.maxMb * 1024 * 1024) {
      emit('reject', `${f.name} is larger than ${props.maxMb} MB.`)
      continue
    }
    ok.push(f)
  }

  const next = props.multiple ? [...props.modelValue, ...ok] : ok.slice(0, 1).length ? ok.slice(0, 1) : props.modelValue
  if (props.multiple && next.length > props.maxFiles) emit('reject', `You can add at most ${props.maxFiles} photos.`)
  emit('update:modelValue', props.multiple ? next.slice(0, props.maxFiles) : next)
}

function remove(i: number) {
  emit('update:modelValue', props.modelValue.filter((_, idx) => idx !== i))
}

const size = (f: File) => (f.size / 1024 / 1024).toFixed(1) + ' MB'
</script>

<template>
  <div>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
      <button
        type="button" :disabled="disabled"
        class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 text-sm font-bold text-white transition hover:bg-red-700 disabled:opacity-50"
        @click="cameraInput?.click()"
      >
        <component :is="kind === 'video' ? Video : Camera" class="h-5 w-5" />
        {{ kind === 'video' ? 'Use device camera' : 'Take photo' }}
      </button>
      <button
        type="button" :disabled="disabled"
        class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-bold text-neutral-900 transition hover:bg-neutral-50 disabled:opacity-50"
        @click="galleryInput?.click()"
      >
        <ImagePlus class="h-5 w-5 text-red-600" />
        {{ kind === 'video' ? 'Choose video' : 'Choose photos' }}
      </button>
    </div>

    <!-- capture opens the camera on phones; the other input opens the gallery / file picker -->
    <input ref="cameraInput" type="file" class="sr-only" tabindex="-1" :accept="accept" capture="environment" @change="add" />
    <input ref="galleryInput" type="file" class="sr-only" tabindex="-1" :accept="accept" :multiple="multiple" @change="add" />

    <ul v-if="previews.length" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
      <li v-for="(p, i) in previews" :key="p.url" class="relative overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50">
        <video v-if="kind === 'video'" :src="p.url" controls playsinline preload="metadata" class="aspect-video w-full bg-black object-contain" />
        <img v-else :src="p.url" :alt="p.file.name" class="aspect-[3/4] w-full object-cover" />
        <p class="truncate px-2 py-1.5 text-[11px] font-medium text-neutral-600">{{ size(p.file) }}</p>
        <button
          type="button" aria-label="Remove" :disabled="disabled"
          class="absolute right-1.5 top-1.5 rounded-full bg-neutral-900/80 p-1 text-white hover:bg-red-600"
          @click="remove(i)"
        ><X class="h-3.5 w-3.5" /></button>
      </li>
    </ul>

    <p v-if="error" class="mt-2 text-xs font-medium text-red-600" role="alert">{{ error }}</p>
    <p v-else class="mt-2 text-xs text-neutral-500">
      {{ kind === 'video' ? `Up to ${maxMb} MB.` : `Up to ${maxFiles} photos, ${maxMb} MB each.` }}
    </p>
  </div>
</template>
