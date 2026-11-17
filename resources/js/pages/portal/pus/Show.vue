<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, MapPin } from 'lucide-vue-next'
import TaskSteps from '@/components/pu/TaskSteps.vue'
import OfficeSelect from '@/components/pu/OfficeSelect.vue'
import { ref, watch } from 'vue'

const props = defineProps<{
  unit: { id: string; name: string; code: string; ward: string | null; lga: string | null; officials_done: boolean; has_video: boolean; sheet_count: number; has_figures: boolean; steps_done: number }
  office: string
  offices: { value: string; label: string }[]
  canGoBack: boolean
}>()

const selectedOffice = ref(props.office)
watch(selectedOffice, (office) => router.get(route('portal.pus.show', { pu: props.unit.id, office }), {}, { preserveState: true, preserveScroll: true, replace: true }))
</script>

<template>
  <Head :title="unit.name" />

  <Link v-if="canGoBack" :href="route('portal.pus', { office })" class="inline-flex items-center gap-1 text-sm font-bold text-red-700 hover:underline">
    <ArrowLeft class="h-4 w-4" /> All polling units
  </Link>

  <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
    <div class="flex h-1.5"><span class="flex-1 bg-red-600" /><span class="flex-1 bg-white" /><span class="flex-1 bg-neutral-900" /></div>
    <div class="p-5">
      <h1 class="text-xl font-black text-neutral-900">{{ unit.name }}</h1>
      <p class="mt-1 flex flex-wrap items-center gap-x-2 text-sm font-medium text-neutral-600">
        <MapPin class="h-4 w-4 text-red-600" />
        <span class="rounded bg-neutral-100 px-1.5 py-0.5 text-xs font-bold">{{ unit.code }}</span>
        <span v-if="unit.ward">{{ unit.ward }} ward</span>
      </p>
      <p class="mt-3 text-sm font-bold" :class="unit.steps_done === 4 ? 'text-neutral-900' : 'text-red-700'">
        {{ unit.steps_done === 4 ? 'All done. Thank you!' : `${unit.steps_done} of 4 tasks done` }}
      </p>
    </div>
  </section>

  <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
    <OfficeSelect v-if="offices.length > 1" v-model="selectedOffice" :offices="offices" />
  </div>

  <TaskSteps :unit="unit" :office="selectedOffice" />
</template>
