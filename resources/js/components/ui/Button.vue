<script setup lang="ts">
/**
 * The only button in the app. Same height, radius and focus ring as the form
 * controls (see components/ui/form/controlClasses.ts). Renders a <button>, an
 * Inertia <Link> (when `href` is set) or a plain <a> (`href` + `external`).
 */
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Loader } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
  variant?: 'primary' | 'secondary' | 'outline' | 'neutral' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  type?: 'button' | 'submit' | 'reset'
  loading?: boolean
  disabled?: boolean
  block?: boolean
  href?: string
  /** Use a plain <a> (downloads, mailto, other sites) instead of an Inertia Link. */
  external?: boolean
}>(), { variant: 'primary', size: 'md', type: 'button', loading: false, disabled: false, block: false, external: false })

const variants = {
  primary: 'border-green-700 bg-green-700 text-white hover:border-green-800 hover:bg-green-800 active:bg-green-900 focus-visible:ring-green-600/30',
  secondary: 'border-[#e8c44a] bg-[#e8c44a] text-[#061a11] hover:bg-[#d9b63d] focus-visible:ring-[#e8c44a]/40',
  outline: 'border-green-700 bg-white text-green-700 hover:bg-green-50 focus-visible:ring-green-600/30',
  neutral: 'border-[#e5e7eb] bg-white text-[#374151] hover:border-[#d1d5db] hover:bg-gray-50 focus-visible:ring-green-600/30',
  ghost: 'border-transparent bg-transparent text-green-700 hover:bg-green-50 focus-visible:ring-green-600/30',
  danger: 'border-[#dc2626] bg-[#dc2626] text-white hover:border-[#b91c1c] hover:bg-[#b91c1c] focus-visible:ring-[#dc2626]/30',
} as const
const sizes = { sm: 'h-8 px-3 text-xs', md: 'h-10 px-4 text-sm', lg: 'h-11 px-5 text-sm' } as const

const classes = computed(() => [
  'inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg border font-semibold outline-none transition-all duration-150 focus-visible:ring-2',
  'aria-disabled:pointer-events-none aria-disabled:opacity-50 disabled:cursor-not-allowed disabled:opacity-50',
  variants[props.variant],
  sizes[props.size],
  props.block && 'w-full',
])

const isLink = computed(() => Boolean(props.href))
const tag = computed(() => (isLink.value ? (props.external ? 'a' : Link) : 'button'))
const inactive = computed(() => props.disabled || props.loading)
</script>

<template>
  <component
    :is="tag"
    :href="href"
    :type="isLink ? undefined : type"
    :disabled="isLink ? undefined : inactive"
    :aria-disabled="isLink && inactive ? 'true' : undefined"
    :aria-busy="loading || undefined"
    :class="classes"
  >
    <Loader v-if="loading" class="h-4 w-4 shrink-0 animate-spin" />
    <slot />
  </component>
</template>
