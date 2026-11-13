<script setup lang="ts">
/**
 * The ONE toast host. It is mounted once in app.ts (outside every layout), so toasts
 * work on login, portal, settings and dashboard pages alike and survive navigation.
 *
 * Sources
 *  - Server flash (`success`, `error`, `warning`): read once per Inertia
 *    visit from the `success` router event, plus once on the first full page load.
 *  - Client calls via useToast(): used by pages after an operation.
 *
 * Duplicates are prevented two ways: flash is read per visit (never from a watcher that
 * can re-fire), and a client toast is dropped when the server already flashed the same
 * kind of message for the same operation, or when the identical text was just shown.
 */
import { router, usePage } from '@inertiajs/vue3'
import { AlertCircle, AlertTriangle, CheckCircle2, Info, X } from 'lucide-vue-next'
import { onBeforeUnmount, onMounted, ref } from 'vue'

type Kind = 'success' | 'error' | 'warning' | 'info'
type Toast = { id: number; message: string; type: Kind }

const MAX_VISIBLE = 4
const LIFETIME_MS = 4500
const SAME_TEXT_MS = 3000
const SERVER_WINS_MS = 2500

const page = usePage()
const items = ref<Toast[]>([])
const timers = new Map<number, number>()
const recent = new Map<string, number>()
const serverShown = new Map<Kind, number>()
let sequence = 0

const clean = (m: string) => m.trim().replace(/\s+/g, ' ')

function add(message: unknown, type: Kind, fromServer = false) {
  if (typeof message !== 'string' || !clean(message)) return
  const text = clean(message)
  const now = Date.now()

  // The server already told the user about this kind of outcome: its message is the source of truth.
  if (!fromServer && now - (serverShown.get(type) ?? 0) < SERVER_WINS_MS) return

  const key = `${type}:${text.toLowerCase()}`
  if (now - (recent.get(key) ?? 0) < SAME_TEXT_MS) return
  recent.set(key, now)
  for (const [k, t] of recent) if (now - t > 10_000) recent.delete(k)
  if (fromServer) serverShown.set(type, now)

  const toast: Toast = { id: ++sequence, message: text, type }
  items.value.push(toast)
  while (items.value.length > MAX_VISIBLE) dismiss(items.value[0].id)
  timers.set(toast.id, window.setTimeout(() => dismiss(toast.id), LIFETIME_MS))
}

function dismiss(id: number) {
  const timer = timers.get(id)
  if (timer) clearTimeout(timer)
  timers.delete(id)
  items.value = items.value.filter((item) => item.id !== id)
}

function readFlash() {
  const flash = ((page.props as any).flash ?? {}) as Record<string, unknown>
  add(flash.success, 'success', true)
  add(flash.error, 'error', true)
  add(flash.warning, 'warning', true)
}

const onToast = (event: Event) => {
  const { message, type } = (event as CustomEvent).detail ?? {}
  add(message, (type as Kind) ?? 'success')
}

let stopSuccess: (() => void) | null = null
onMounted(() => {
  window.addEventListener('app:toast', onToast)
  readFlash() // first full page load (e.g. redirected after login or logout)
  stopSuccess = router.on('success', readFlash) // every later visit, exactly once
})
onBeforeUnmount(() => {
  window.removeEventListener('app:toast', onToast)
  stopSuccess?.()
  timers.forEach((t) => clearTimeout(t))
})

const styles: Record<Kind, { border: string; icon: string }> = {
  success: { border: 'border-green-200', icon: 'text-green-600' },
  error: { border: 'border-red-200', icon: 'text-red-600' },
  warning: { border: 'border-amber-200', icon: 'text-amber-600' },
  info: { border: 'border-blue-200', icon: 'text-blue-600' },
}
const icons = { success: CheckCircle2, error: AlertCircle, warning: AlertTriangle, info: Info }
</script>

<template>
  <div class="pointer-events-none fixed top-4 right-4 z-[10000] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2" aria-live="polite" aria-atomic="false">
    <TransitionGroup name="toast">
      <div
        v-for="toast in items"
        :key="toast.id"
        class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 shadow-lg"
        :class="styles[toast.type].border"
        :role="toast.type === 'error' ? 'alert' : 'status'"
      >
        <component :is="icons[toast.type]" class="mt-0.5 h-5 w-5 shrink-0" :class="styles[toast.type].icon" />
        <p class="min-w-0 flex-1 text-sm break-words text-gray-700">{{ toast.message }}</p>
        <button type="button" aria-label="Dismiss notification" class="rounded p-1 text-gray-400 hover:bg-gray-100" @click="dismiss(toast.id)"><X class="h-4 w-4" /></button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from { opacity: 0; transform: translateX(1rem); }
.toast-leave-to { opacity: 0; transform: translateX(1rem); }
.toast-move { transition: transform 0.25s ease; }
</style>
