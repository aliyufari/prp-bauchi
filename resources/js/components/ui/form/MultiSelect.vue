<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref, useId, watch } from 'vue'
import { Check, ChevronDown, Search, X } from 'lucide-vue-next'
import type { SelectOption, SelectValue } from './types'
import FormField from './FormField.vue'
import { controlClasses } from './controlClasses'

const props = withDefaults(defineProps<{
  modelValue?: SelectValue[]
  options: SelectOption[]
  label?: string
  hint?: string
  error?: string | string[] | null
  placeholder?: string
  searchPlaceholder?: string
  id?: string
  required?: boolean
  disabled?: boolean
  maxChips?: number
  selectAll?: boolean
}>(), {
  modelValue: () => [], placeholder: 'Select options', searchPlaceholder: 'Search…',
  required: false, disabled: false, maxChips: 3, selectAll: true,
})
const emit = defineEmits<{ 'update:modelValue': [value: SelectValue[]] }>()
const generatedId = useId()
const fieldId = computed(() => props.id || generatedId)
const root = ref<HTMLElement | null>(null)
const query = ref('')
const open = ref(false)
const filtered = computed(() => {
  const term = query.value.trim().toLowerCase()
  return props.options.filter(o => !term || `${o.label} ${o.description ?? ''}`.toLowerCase().includes(term))
})
const chosen = computed(() => props.modelValue.map(v => props.options.find(o => o.value === v) ?? { value: v, label: String(v) }))
const visible = computed(() => chosen.value.slice(0, props.maxChips))
const allVisibleSelected = computed(() => filtered.value.filter(o => !o.disabled).length > 0 && filtered.value.filter(o => !o.disabled).every(o => props.modelValue.includes(o.value)))
const isSelected = (value: SelectValue) => props.modelValue.includes(value)
function toggleValue(option: SelectOption) {
  if (option.disabled) return
  const next = isSelected(option.value)
    ? props.modelValue.filter(v => v !== option.value)
    : [...props.modelValue, option.value]
  emit('update:modelValue', next)
}
function toggleAll() {
  const values = filtered.value.filter(o => !o.disabled).map(o => o.value)
  emit('update:modelValue', allVisibleSelected.value
    ? props.modelValue.filter(v => !values.includes(v))
    : [...new Set([...props.modelValue, ...values])])
}
function remove(value: SelectValue) { emit('update:modelValue', props.modelValue.filter(v => v !== value)) }
function handleOutsideClick(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) open.value = false
}
onMounted(() => document.addEventListener('mousedown', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleOutsideClick))
watch(open, value => { if (!value) query.value = '' })
</script>

<template>
  <FormField :id="fieldId" :label="label" :hint="hint" :error="error" :required="required">
    <template #default="{ id: resolvedId }">
      <div ref="root" class="relative">
        <button :id="resolvedId" type="button" :disabled="disabled" :aria-expanded="open"
          aria-haspopup="listbox" :class="[controlClasses(error, disabled), 'flex min-h-10 items-center gap-2 text-left']"
          @click="open = !open">
          <span v-if="!chosen.length" class="flex-1 text-[#9ca3af]">{{ placeholder }}</span>
          <span v-else class="flex min-w-0 flex-1 flex-wrap gap-1">
            <span v-for="item in visible" :key="String(item.value)" class="inline-flex max-w-full items-center gap-1 rounded-md bg-[#dcfce7] px-2 py-1 text-xs font-medium text-[#0d3b26]">
              <span class="max-w-36 truncate">{{ item.label }}</span>
              <X class="h-3 w-3 cursor-pointer" @click.stop="remove(item.value)" />
            </span>
            <span v-if="chosen.length > maxChips" class="rounded-md bg-[#f3f4f6] px-2 py-1 text-xs text-[#4b5563]">+{{ chosen.length - maxChips }} more</span>
          </span>
          <ChevronDown class="h-4 w-4 shrink-0 text-[#6b7280]" />
        </button>
        <div v-if="open" class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-[#d1d5db] bg-white shadow-xl">
          <div class="relative border-b border-[#e5e7eb] p-2">
            <Search class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9ca3af]" />
            <input v-model="query" :placeholder="searchPlaceholder" class="h-9 w-full rounded-md border border-[#e5e7eb] pl-9 pr-3 text-sm outline-none focus:border-[#16a34a] focus:ring-2 focus:ring-[#16a34a]/15" />
          </div>
          <button v-if="selectAll && filtered.length" type="button" class="flex w-full items-center justify-between border-b border-[#e5e7eb] px-3 py-2 text-xs font-medium text-[#374151] hover:bg-[#f9fafb]" @click="toggleAll">
            <span>{{ allVisibleSelected ? 'Clear visible options' : 'Select all visible' }}</span>
            <span>{{ props.modelValue.length }} selected</span>
          </button>
          <ul role="listbox" aria-multiselectable="true" class="max-h-60 overflow-y-auto py-1">
            <li v-if="!filtered.length" class="px-3 py-3 text-sm text-[#6b7280]">No matching options</li>
            <li v-for="option in filtered" :key="`${typeof option.value}:${option.value}`"
              role="option" :aria-selected="isSelected(option.value)"
              :class="['flex cursor-pointer items-center gap-2 px-3 py-2 text-sm hover:bg-[#f0fdf4]', option.disabled && 'cursor-not-allowed opacity-40']"
              @click="toggleValue(option)">
              <span class="flex h-4 w-4 items-center justify-center rounded border" :class="isSelected(option.value) ? 'border-[#16a34a] bg-[#16a34a] text-white' : 'border-[#9ca3af]'">
                <Check v-if="isSelected(option.value)" class="h-3 w-3" />
              </span>
              <span class="min-w-0 flex-1">
                <span class="block truncate text-[#111827]">{{ option.label }}</span>
                <span v-if="option.description" class="block truncate text-xs text-[#6b7280]">{{ option.description }}</span>
              </span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </FormField>
</template>
