<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps<{ status: number; message?: string | null }>()
const { auth } = usePermissions()

const COPY: Record<number, { title: string; body: string }> = {
  403: { title: 'Access denied', body: "You don't have permission to do that." },
  404: { title: 'Not found', body: "This page doesn't exist, or it's outside the area you manage." },
  500: { title: 'Something went wrong', body: 'We hit an error on our side. Please try again.' },
  503: { title: 'Back shortly', body: 'The system is down for maintenance.' },
}
const copy = computed(() => COPY[props.status] ?? COPY[500])
</script>

<template>
  <Head :title="copy.title" />
  <div class="flex min-h-screen items-center justify-center bg-neutral-50 p-6">
    <div class="w-full max-w-md rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-sm">
      <p class="text-5xl font-black text-red-700">{{ status }}</p>
      <h1 class="mt-3 text-lg font-bold text-gray-900">{{ copy.title }}</h1>
      <p class="mt-2 text-sm text-gray-500">{{ message || copy.body }}</p>
      <div class="mt-6 flex justify-center gap-3">
        <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-500 hover:bg-gray-100" @click="history.back()">Go back</button>
        <Link :href="auth?.home ?? '/'" class="rounded-xl bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Home</Link>
      </div>
    </div>
  </div>
</template>