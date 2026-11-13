type Kind = 'success' | 'error' | 'warning' | 'info'

const emit = (type: Kind, message: string) => window.dispatchEvent(new CustomEvent('app:toast', { detail: { message, type } }))

/** Client-side toasts. The single ToastHost (mounted in app.ts) renders and de-duplicates them. */
export function useToast() {
  return {
    success: (message: string) => emit('success', message),
    error: (message: string) => emit('error', message),
    warning: (message: string) => emit('warning', message),
    info: (message: string) => emit('info', message),
  }
}
