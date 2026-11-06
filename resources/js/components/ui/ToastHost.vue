<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { CheckCircle2, AlertCircle, X } from 'lucide-vue-next'

type Toast = { id: number; message: string; type: 'success' | 'error' | 'info' }
const page = usePage()
const items = ref<Toast[]>([])
let sequence = 0
const recentMessages = new Map<string, number>()
function add(message: unknown, type: Toast['type'] = 'success') {
  if (typeof message !== 'string' || !message.trim()) return

  // A successful Inertia visit can deliver the same message through both
  // flash props and the app:toast event. Suppress that duplicate notification.
  const normalized = message.trim().replace(/\s+/g, ' ').toLocaleLowerCase()
  const fingerprint = `${type}:${normalized}`
  const now = Date.now()
  const previous = recentMessages.get(fingerprint)
  if (previous && now - previous < 1800) return
  recentMessages.set(fingerprint, now)
  for (const [key, timestamp] of recentMessages) {
    if (now - timestamp >= 5000) recentMessages.delete(key)
  }

  const toast = { id: ++sequence, message: message.trim(), type }
  items.value.push(toast)
  window.setTimeout(() => dismiss(toast.id), 4500)
}
function dismiss(id: number) { items.value = items.value.filter(item => item.id !== id) }
const onToast = (event: Event) => {
  const detail = (event as CustomEvent).detail
  add(detail?.message, detail?.type ?? 'success')
}
onMounted(() => window.addEventListener('app:toast', onToast))
onBeforeUnmount(() => window.removeEventListener('app:toast', onToast))
watch(() => (page.props as any).flash, flash => {
  if (flash?.success) add(flash.success, 'success')
  if (flash?.error) add(flash.error, 'error')
}, { deep: true, immediate: true })
</script>
<template>
  <div class="pointer-events-none fixed right-4 top-4 z-[200] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2" aria-live="polite" aria-atomic="false">
    <TransitionGroup name="toast">
      <div v-for="toast in items" :key="toast.id" class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 shadow-lg"
        :class="toast.type === 'error' ? 'border-red-200' : 'border-green-200'">
        <CheckCircle2 v-if="toast.type === 'success'" class="mt-0.5 h-5 w-5 shrink-0 text-green-600" />
        <AlertCircle v-else class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
        <p class="min-w-0 flex-1 text-sm text-gray-700">{{ toast.message }}</p>
        <button type="button" aria-label="Dismiss notification" class="rounded p-1 text-gray-400 hover:bg-gray-100" @click="dismiss(toast.id)"><X class="h-4 w-4" /></button>
      </div>
    </TransitionGroup>
  </div>
</template>
