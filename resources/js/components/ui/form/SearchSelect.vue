<script setup lang="ts">
import { computed, nextTick, onMounted, onBeforeUnmount, ref, useId, watch } from 'vue'
import { Check, ChevronDown, Search, X } from 'lucide-vue-next'
import type { SelectOption, SelectValue } from './types'
import FormField from './FormField.vue'
import { controlClasses, searchBoxClasses } from './controlClasses'

const props = withDefaults(defineProps<{
  modelValue?: SelectValue | null
  options: SelectOption[]
  label?: string
  hint?: string
  error?: string | string[] | null
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  id?: string
  required?: boolean
  disabled?: boolean
}>(), {
  placeholder: 'Choose an option', searchPlaceholder: 'Search options…',
  emptyText: 'No matching options', required: false, disabled: false,
})
const emit = defineEmits<{ 'update:modelValue': [value: SelectValue | null] }>()
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)
const root = ref<HTMLElement | null>(null)
const search = ref('')
const open = ref(false)
const activeIndex = ref(0)
const searchEl = ref<HTMLInputElement | null>(null)
const selected = computed(() => props.options.find(o => o.value === props.modelValue))
const filtered = computed(() => {
  const term = search.value.trim().toLocaleLowerCase()
  return props.options.filter(o => !term || `${o.label} ${o.description ?? ''}`.toLocaleLowerCase().includes(term))
})
watch(search, () => { activeIndex.value = 0 })
function handleOutsideClick(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) open.value = false
}
onMounted(() => document.addEventListener('mousedown', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleOutsideClick))
async function toggle() {
  if (props.disabled) return
  open.value = !open.value
  if (open.value) { search.value = ''; await nextTick(); searchEl.value?.focus() }
}
function choose(option: SelectOption) {
  if (option.disabled) return
  emit('update:modelValue', option.value)
  open.value = false
}
function keydown(event: KeyboardEvent) {
  if (event.key === 'ArrowDown') { event.preventDefault(); activeIndex.value = Math.min(activeIndex.value + 1, filtered.value.length - 1) }
  if (event.key === 'ArrowUp') { event.preventDefault(); activeIndex.value = Math.max(activeIndex.value - 1, 0) }
  if (event.key === 'Enter' && open.value && filtered.value[activeIndex.value]) { event.preventDefault(); choose(filtered.value[activeIndex.value]) }
  if (event.key === 'Escape') open.value = false
}
</script>

<template>
  <FormField :id="fieldId" :label="label" :hint="hint" :error="error" :required="required">
    <template #default="{ id: resolvedId }">
      <div ref="root" class="relative">
        <button :id="resolvedId" type="button" :disabled="disabled" :aria-expanded="open"
          aria-haspopup="listbox" :class="[controlClasses(error, disabled), 'flex items-center justify-between gap-2 text-left']"
          @click="toggle" @keydown="keydown">
          <span :class="selected ? 'truncate text-[#111827]' : 'truncate text-[#9ca3af]'">{{ selected?.label ?? placeholder }}</span>
          <span class="flex shrink-0 items-center gap-1">
            <X v-if="selected" class="h-4 w-4 text-[#9ca3af] hover:text-[#dc2626]" @click.stop="emit('update:modelValue', null)" />
            <ChevronDown class="h-4 w-4 text-[#6b7280]" />
          </span>
        </button>
        <div v-if="open" class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-[#e5e7eb] bg-white shadow-lg">
          <div class="relative border-b border-[#e5e7eb] p-2">
            <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9ca3af]" />
            <input ref="searchEl" v-model="search" :placeholder="searchPlaceholder"
              :class="searchBoxClasses"
              @keydown="keydown" />
          </div>
          <ul role="listbox" class="max-h-60 overflow-y-auto py-1">
            <li v-if="!filtered.length" class="px-3 py-3 text-sm text-[#6b7280]">{{ emptyText }}</li>
            <li v-for="(option, index) in filtered" :key="`${typeof option.value}:${option.value}`"
              role="option" :aria-selected="option.value === modelValue"
              :class="['flex cursor-pointer items-center gap-2 px-3 py-2 text-sm', index === activeIndex ? 'bg-[#f0fdf4]' : 'hover:bg-[#f9fafb]', option.disabled && 'cursor-not-allowed opacity-40']"
              @mouseenter="activeIndex = index" @click="choose(option)">
              <span class="min-w-0 flex-1">
                <span class="block truncate text-[#111827]">{{ option.label }}</span>
                <span v-if="option.description" class="block truncate text-xs text-[#6b7280]">{{ option.description }}</span>
              </span>
              <Check v-if="option.value === modelValue" class="h-4 w-4 text-[#16a34a]" />
            </li>
          </ul>
        </div>
      </div>
    </template>
  </FormField>
</template>
