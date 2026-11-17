<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowLeft, MapPin } from 'lucide-vue-next'

defineProps<{
  pu: { id: string; name: string; code: string; ward: string | null; lga: string | null }
  title: string
  subtitle?: string
  office?: string
}>()
</script>

<template>
  <div>
    <Link :href="route('portal.pus.show', { pu: pu.id, office })" class="inline-flex items-center gap-1 text-sm font-bold text-red-700 hover:underline">
      <ArrowLeft class="h-4 w-4" /> Back
    </Link>
    <div class="mt-2 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
      <div class="flex h-1.5"><span class="flex-1 bg-red-600" /><span class="flex-1 bg-white" /><span class="flex-1 bg-neutral-900" /></div>
      <div class="p-5">
        <h1 class="text-xl font-black text-neutral-900">{{ title }}</h1>
        <p v-if="subtitle" class="mt-1 text-base text-neutral-600">{{ subtitle }}</p>
        <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-neutral-600">
          <MapPin class="h-3.5 w-3.5 text-red-600" />
          <span class="text-neutral-900">{{ pu.name }}</span>
          <span class="rounded bg-neutral-100 px-1.5 py-0.5">{{ pu.code }}</span>
        </p>
      </div>
    </div>
  </div>
</template>
