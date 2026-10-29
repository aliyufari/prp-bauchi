<script setup lang="ts">
import { computed, ref, type Component } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
  modelValue: string
  id: string
  label: string
  type?: string
  icon?: Component
  error?: string
  placeholder?: string
  autocomplete?: string
  tabindex?: number
  autofocus?: boolean
  readonly?: boolean
}>(), { type: 'text' })

defineEmits<{ 'update:modelValue': [value: string] }>()

const reveal = ref(false)
const isPassword = computed(() => props.type === 'password')
const inputType = computed(() => (isPassword.value && reveal.value ? 'text' : props.type))
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <div class="flex items-center justify-between flex-wrap gap-1">
      <label :for="id" class="text-[#374151] text-sm font-semibold">{{ label }}</label>
      <slot name="label-aside" />
    </div>

    <div class="relative">
      <component :is="icon" v-if="icon" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9ca3af] pointer-events-none" />
      <input
        :id="id"
        :value="modelValue"
        :type="inputType"
        :tabindex="tabindex"
        :autocomplete="autocomplete"
        :placeholder="placeholder"
        :autofocus="autofocus"
        :readonly="readonly"
        required
        :class="[
          'w-full py-2.5 rounded-lg border text-sm text-[#111827] bg-white placeholder:text-[#d1d5db] outline-none transition-all duration-150',
          'focus:ring-2 focus:ring-[#16a34a]/30 focus:border-[#16a34a]',
          icon ? 'pl-10' : 'pl-4',
          isPassword ? 'pr-10' : 'pr-4',
          readonly ? 'bg-gray-50 text-gray-500' : '',
          error ? 'border-[#dc2626] bg-[#fef2f2]' : 'border-[#e5e7eb] hover:border-[#d1d5db]',
        ]"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
      <button
        v-if="isPassword"
        type="button"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9ca3af] hover:text-[#374151] transition-colors"
        :aria-label="reveal ? 'Hide password' : 'Show password'"
        @click="reveal = !reveal"
      >
        <EyeOff v-if="reveal" class="w-4 h-4" />
        <Eye v-else class="w-4 h-4" />
      </button>
    </div>

    <p v-if="error" class="text-[#dc2626] text-xs font-medium flex items-center gap-1 mt-0.5">
      <span class="w-1 h-1 rounded-full bg-[#dc2626] inline-block" />
      {{ error }}
    </p>
  </div>
</template>