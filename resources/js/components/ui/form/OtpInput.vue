<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
const props = withDefaults(defineProps<{ modelValue?: string; length?: number; disabled?: boolean; error?: boolean }>(), { modelValue: '', length: 6, disabled: false, error: false })
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const inputs = ref<HTMLInputElement[]>([])
const digits = computed(() => Array.from({ length: props.length }, (_, i) => props.modelValue[i] ?? ''))
function setRef(el: unknown, index: number) { if (el instanceof HTMLInputElement) inputs.value[index] = el }
function update(index: number, event: Event) {
  const digit = (event.target as HTMLInputElement).value.replace(/\D/g, '').slice(-1)
  const next = digits.value.slice()
  next[index] = digit
  emit('update:modelValue', next.join(''))
  if (digit && index < props.length - 1) nextTick(() => inputs.value[index + 1]?.focus())
}
function keydown(index: number, event: KeyboardEvent) {
  if (event.key === 'Backspace' && !digits.value[index] && index > 0) inputs.value[index - 1]?.focus()
  if (event.key === 'ArrowLeft' && index > 0) inputs.value[index - 1]?.focus()
  if (event.key === 'ArrowRight' && index < props.length - 1) inputs.value[index + 1]?.focus()
}
function paste(event: ClipboardEvent) {
  const pasted = event.clipboardData?.getData('text').replace(/\D/g, '').slice(0, props.length)
  if (pasted) { event.preventDefault(); emit('update:modelValue', pasted); nextTick(() => inputs.value[Math.min(pasted.length, props.length - 1)]?.focus()) }
}
</script>
<template>
  <div class="flex gap-2" @paste="paste">
    <input v-for="(_, index) in digits" :key="index" :ref="el => setRef(el, index)" :value="digits[index]"
      inputmode="numeric" autocomplete="one-time-code" maxlength="1" :disabled="disabled"
      :aria-label="`Verification digit ${index + 1}`"
      :class="['h-12 w-10 rounded-lg border bg-white text-center text-lg font-semibold text-[#0d3b26] outline-none focus:border-[#16a34a] focus:ring-2 focus:ring-[#16a34a]/20', error ? 'border-[#dc2626]' : 'border-[#d1d5db]', disabled && 'bg-[#f9fafb] opacity-50']"
      @input="update(index, $event)" @keydown="keydown(index, $event)" />
  </div>
</template>
