/**
 * Shared class names aligned with the host application's green/gold palette.
 * No theme package or runtime dependency is required.
 */
export const controlBase =
  'h-10 w-full rounded-xl border bg-white px-3 text-sm text-[#111827] shadow-sm outline-none transition-colors placeholder:text-[#9ca3af]'

export const controlNormal =
  'border-[#d1d5db] hover:border-[#9ca3af] focus:border-[#16a34a] focus:ring-2 focus:ring-[#16a34a]/20'

export const controlInvalid =
  'border-[#dc2626] bg-[#fef2f2] focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/15'

export const controlDisabled =
  'cursor-not-allowed border-[#e5e7eb] bg-[#f9fafb] text-[#9ca3af]'

export function controlClasses(error?: string | string[] | null, disabled = false) {
  const hasError = Array.isArray(error) ? error.length > 0 : Boolean(error)
  return [
    controlBase,
    disabled ? controlDisabled : hasError ? controlInvalid : controlNormal,
  ]
}
