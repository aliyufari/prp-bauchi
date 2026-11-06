<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { AlertCircle } from 'lucide-vue-next'

const page = usePage()
const errors = computed(() => {
  const raw = (page.props as any).errors as Record<string, string | string[]> | undefined
  return Object.entries(raw ?? {}).flatMap(([field, message]) => {
    const messages = Array.isArray(message) ? message : [message]
    return messages.filter(Boolean).map((text) => ({ field, message: text }))
  })
})
</script>

<template>
  <section
    v-if="errors.length"
    role="alert"
    aria-live="assertive"
    class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"
  >
    <div class="flex items-start gap-2">
      <AlertCircle class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
      <div class="min-w-0">
        <p class="font-semibold">Please correct the following errors:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
          <li v-for="(error, index) in errors" :key="`${error.field}-${index}`">
            {{ error.message }}
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>
