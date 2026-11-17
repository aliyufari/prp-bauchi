<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { Camera, CircleStop, Video } from 'lucide-vue-next'

const props = defineProps<{ maxMb: number; disabled?: boolean }>()
const emit = defineEmits<{ recorded: [file: File] }>()

const preview = ref<HTMLVideoElement | null>(null)
const stream = ref<MediaStream | null>(null)
const recorder = ref<MediaRecorder | null>(null)
const chunks: BlobPart[] = []
const starting = ref(false)
const recording = ref(false)
const audioAvailable = ref(true)
const error = ref('')

function stopTracks() {
  stream.value?.getTracks().forEach((track) => track.stop())
  stream.value = null
  if (preview.value) preview.value.srcObject = null
}

async function requestCamera(): Promise<MediaStream> {
  if (!window.isSecureContext) {
    throw new Error('Camera access requires HTTPS. Open this site using its secure HTTPS address, then allow camera permission.')
  }
  if (!navigator.mediaDevices?.getUserMedia) {
    throw new Error('This browser does not provide camera access. Update Chrome or use the device camera button below.')
  }

  // Some phones reject the entire request when a microphone is unavailable or
  // microphone permission is denied. Retry with video-only so the camera can
  // still be used; audio is optional for this evidence recording.
  const requests: MediaStreamConstraints[] = [
    { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: true },
    { video: { facingMode: { ideal: 'environment' } }, audio: false },
    { video: true, audio: false },
  ]
  let lastError: unknown
  for (const constraints of requests) {
    try {
      return await navigator.mediaDevices.getUserMedia(constraints)
    } catch (cause) {
      lastError = cause
    }
  }

  const name = lastError instanceof DOMException ? lastError.name : ''
  if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
    throw new Error('Camera permission was denied. Allow camera access for this site in your browser settings, then try again.')
  }
  if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
    throw new Error('No camera was found on this device. Use the device camera button below or choose an existing video.')
  }
  if (name === 'NotReadableError' || name === 'TrackStartError') {
    throw new Error('The camera is busy in another app. Close other camera apps or browser tabs and try again.')
  }
  throw new Error('The camera could not be opened. Check site permissions, close other apps using the camera, or use the device camera button below.')
}

async function startRecording() {
  if (props.disabled || starting.value || recording.value) return
  error.value = ''
  starting.value = true

  try {
    if (!('MediaRecorder' in window)) {
      throw new Error('Live recording is not supported by this browser. Use the device camera button below instead.')
    }

    const activeStream = await requestCamera()
    stream.value = activeStream
    audioAvailable.value = activeStream.getAudioTracks().length > 0

    if (!preview.value) throw new Error('The camera preview is not ready. Please try again.')
    preview.value.srcObject = activeStream
    preview.value.muted = true
    preview.value.playsInline = true
    await preview.value.play()

    const supportedTypes = [
      'video/webm;codecs=vp8,opus',
      'video/webm;codecs=vp9,opus',
      'video/webm',
      'video/mp4',
    ]
    const mimeType = typeof MediaRecorder.isTypeSupported === 'function'
      ? supportedTypes.find((type) => MediaRecorder.isTypeSupported(type))
      : undefined
    recorder.value = mimeType ? new MediaRecorder(activeStream, { mimeType }) : new MediaRecorder(activeStream)
    chunks.length = 0
    recorder.value.ondataavailable = (event) => {
      if (event.data.size > 0) chunks.push(event.data)
    }
    recorder.value.onerror = () => {
      error.value = 'The recording stopped unexpectedly. Please try again.'
      recording.value = false
      stopTracks()
    }
    recorder.value.onstop = () => {
      const type = recorder.value?.mimeType || (chunks[0] as Blob | undefined)?.type || 'video/webm'
      const blob = new Blob(chunks, { type })
      const extension = type.includes('mp4') ? 'mp4' : 'webm'
      const file = new File([blob], `counting-video-${Date.now()}.${extension}`, { type })
      if (file.size > props.maxMb * 1024 * 1024) {
        error.value = `This recording is larger than ${props.maxMb} MB. Please record a shorter video.`
      } else if (file.size > 0) {
        emit('recorded', file)
      } else {
        error.value = 'No video was captured. Please try again.'
      }
      recording.value = false
      stopTracks()
    }

    recorder.value.start(1000)
    recording.value = true
  } catch (cause) {
    stopTracks()
    error.value = cause instanceof Error ? cause.message : 'Camera access failed. Check the camera permission and try again.'
  } finally {
    starting.value = false
  }
}

function stopRecording() {
  if (recorder.value && recorder.value.state !== 'inactive') recorder.value.stop()
}

onBeforeUnmount(() => {
  if (recorder.value && recorder.value.state !== 'inactive') recorder.value.stop()
  stopTracks()
})
</script>

<template>
  <section class="space-y-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
    <div class="flex items-center gap-2">
      <Camera class="h-5 w-5 text-red-600" />
      <div>
        <h3 class="text-sm font-black text-neutral-900">Record video here</h3>
        <p class="text-xs text-neutral-500">The camera opens on this page. Keep the PO and result sheet in view.</p>
      </div>
    </div>

    <video ref="preview" autoplay muted playsinline class="aspect-video w-full rounded-xl bg-black object-contain" />

    <div class="flex flex-wrap gap-2">
      <button v-if="!recording" type="button" :disabled="disabled || starting" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 text-sm font-black text-white hover:bg-red-700 disabled:opacity-50" @click="startRecording">
        <Video class="h-4 w-4" />{{ starting ? 'Opening camera…' : 'Start recording' }}
      </button>
      <button v-else type="button" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-neutral-900 px-4 text-sm font-black text-white hover:bg-neutral-800" @click="stopRecording">
        <CircleStop class="h-4 w-4" />Stop and use this video
      </button>
    </div>

    <p v-if="!audioAvailable" class="text-xs font-semibold text-amber-700" role="status">The microphone is unavailable or not permitted. This recording will have no sound.</p>
    <p v-if="recording" class="text-xs font-bold text-red-700" role="status">Recording… press Stop when the counting and result presentation are complete.</p>
    <p v-if="error" class="text-xs font-semibold text-red-700" role="alert">{{ error }}</p>
    <p class="text-[11px] leading-relaxed text-neutral-500">Allow camera permission when prompted. Live recording needs HTTPS (or localhost); audio is optional. The video is not sent until you press Send video.</p>
  </section>
</template>
