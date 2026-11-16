import '@/theme/party-agent.css'
import { usePermissions } from '@/composables/usePermissions'
import { onBeforeUnmount, watchEffect } from 'vue'

/**
 * Role based colour themes. The app's palette is green by default. A theme re-points the
 * Tailwind `green` scale and the brand variables used by the header / sidebar, so every
 * component that uses green automatically follows (no per-component changes needed).
 *
 * Party agents: PRP red, black and white.
 */
const RED_SCALE: Record<string, string> = {
  50: '#fafafa', 100: '#fee2e2', 200: '#fecaca', 300: '#fca5a5', 400: '#f87171',
  500: '#ef4444', 600: '#dc2626', 700: '#b91c1c', 800: '#991b1b', 900: '#7f1d1d', 950: '#450a0a',
}

const PARTY_AGENT_THEME: Record<string, string> = {
  ...Object.fromEntries(Object.entries(RED_SCALE).map(([k, v]) => [`--color-green-${k}`, v])),
  '--brand-side-from': '#1f1f1f',
  '--brand-side-via': '#0f0f0f',
  '--brand-side-to': '#000000',
  '--brand-accent': '#ef4444',
  '--brand-accent-dark': '#b91c1c',
  '--brand-ring': 'rgba(239,68,68,0.35)',
  '--brand-active-from': '#ef4444',
  '--brand-active-to': '#b91c1c',
  '--brand-active-shadow': 'rgba(220,38,38,0.4)',
  '--brand-avatar-from': '#dc2626',
  '--brand-avatar-to': '#7f1d1d',
  '--brand-soft': '#fca5a5',
  '--brand-badge-bg': 'rgba(255,255,255,0.12)',
  '--brand-badge-text': '#ffffff',
}

const THEME_ROLES = ['party_agent', 'super_party_agent']

export function useRoleTheme() {
  const { isRole } = usePermissions()
  let applied: string[] = []

  const clear = () => {
    const root = document.documentElement
    applied.forEach((name) => root.style.removeProperty(name))
    applied = []
    root.removeAttribute('data-theme-role')
  }

  watchEffect(() => {
    if (typeof document === 'undefined') return
    clear()
    if (!isRole(...THEME_ROLES)) return
    const root = document.documentElement
    for (const [name, value] of Object.entries(PARTY_AGENT_THEME)) {
      root.style.setProperty(name, value)
      applied.push(name)
    }
    root.setAttribute('data-theme-role', 'party-agent')
  })

  onBeforeUnmount(() => {
    /* layouts swap on navigation; the next layout re-applies the theme immediately */
  })
}
