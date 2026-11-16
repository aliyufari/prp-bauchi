/**
 * Single source of truth for every text-like form control in the app
 * (Input, Textarea, Select, SearchSelect, MultiSelect, DatePicker, search boxes, ...).
 * The look is the one used on the login / register screens: 8px radius, soft gray
 * border, green focus ring, no drop shadow. Change it here and the whole app follows.
 */
export const controlBase =
  'w-full rounded-lg border bg-white text-sm text-[#111827] outline-none transition-all duration-150 placeholder:text-[#d1d5db]'

const controlSize = {
  md: 'h-10 px-4',
  sm: 'h-8 px-2.5 text-xs font-medium',
  /** Grows with its content (textareas, multi-select chips). */
  auto: 'min-h-10 px-4 py-2',
} as const

export const controlNormal =
  'border-[#e5e7eb] hover:border-[#d1d5db] focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/30'

export const controlInvalid =
  'border-[#dc2626] bg-[#fef2f2] focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/20'

export const controlDisabled =
  'cursor-not-allowed border-[#e5e7eb] bg-[#f9fafb] text-[#9ca3af]'

export const controlReadonly = 'cursor-default bg-gray-50 text-gray-500'

export function controlClasses(
  error?: string | string[] | null,
  disabled = false,
  size: keyof typeof controlSize = 'md',
) {
  const hasError = Array.isArray(error) ? error.length > 0 : Boolean(error)
  return [
    controlBase,
    controlSize[size],
    disabled ? controlDisabled : hasError ? controlInvalid : controlNormal,
  ]
}
