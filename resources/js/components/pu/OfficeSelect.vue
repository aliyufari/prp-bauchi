<script setup lang="ts">
/** Election/office switcher shared by the agent task list and the admin PU reports. */
defineProps<{ modelValue: string; offices: { value: string; label: string }[] }>()
defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <label class="block">
    <span class="mb-1 block text-xs font-bold uppercase tracking-wider text-neutral-500">Election / office</span>
    <select
      :value="modelValue"
      class="h-10 w-full rounded-lg border border-[#e5e7eb] bg-white px-3 text-sm font-semibold text-[#111827] outline-none [-webkit-text-fill-color:#111827] focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/30 sm:max-w-xs"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option v-for="o in offices" :key="o.value" :value="o.value">{{ o.label }}</option>
    </select>
  </label>
</template>
