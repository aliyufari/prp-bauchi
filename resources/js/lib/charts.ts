/** Helpers that make chart components tolerant of whatever the API sends (strings, nulls, missing fields). */

/** Coerces to a finite, non-negative number; anything else becomes 0. */
export function num(value: unknown): number {
  const n = typeof value === 'string' ? Number(value.replace(/,/g, '')) : Number(value)
  return Number.isFinite(n) && n > 0 ? n : 0
}

export const compact = (n: number): string =>
  n >= 1_000_000 ? `${+(n / 1_000_000).toFixed(1)}M` : n >= 1000 ? `${+(n / 1000).toFixed(1)}k` : String(n)

export const percentOf = (part: number, total: number): number => (total > 0 ? Math.round((part / total) * 100) : 0)

/** Brand-aligned fallback palette for slices that arrive without a colour. */
export const PALETTE = ['#dc2626', '#171717', '#a3a3a3', '#f87171', '#525252', '#b91c1c', '#d4d4d4', '#7f1d1d']

const HEX = /^#(?:[0-9a-f]{3}){1,2}$/i
export const colorOr = (value: unknown, index: number): string =>
  typeof value === 'string' && (HEX.test(value) || /^(rgb|hsl)a?\(/i.test(value)) ? value : PALETTE[index % PALETTE.length]

export const escapeHtml = (s: string): string =>
  s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] as string)
