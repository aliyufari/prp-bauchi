<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ChevronRight, Search } from 'lucide-vue-next'
import TaskStatus from '@/components/pu/TaskStatus.vue'
import OfficeSelect from '@/components/pu/OfficeSelect.vue'
import Pagination from '@/components/ui/Pagination.vue'

interface PuRow {
  id: string; name: string; code: string; ward: string | null
  officials_done: boolean; has_video: boolean; sheet_count: number; has_figures: boolean; steps_done: number
}
const props = defineProps<{
  scopeName: string | null
  pus: { data: PuRow[]; meta: { current_page: number; last_page: number; total: number; per_page: number; from: number | null; to: number | null } }
  counts: { all: number; new: number; progress: number; done: number }
  filters: { search?: string | null; status?: string }
  office: string
  offices: { value: string; label: string }[]
}>()

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const office = ref(props.office)
let timer: ReturnType<typeof setTimeout>
function visit(page = 1) {
  router.get(route('portal.pus'), {
    office: office.value, search: search.value || undefined, status: status.value || undefined, page: page > 1 ? page : undefined,
  }, { preserveState: true, replace: true })
}
watch(search, () => { clearTimeout(timer); timer = setTimeout(() => visit(), 350) })
watch([status, office], () => visit())

const tabs = [
  { value: '', label: 'All', count: () => props.counts.all },
  { value: 'new', label: 'Not started', count: () => props.counts.new },
  { value: 'progress', label: 'Not finished', count: () => props.counts.progress },
  { value: 'done', label: 'Finished', count: () => props.counts.done },
]
</script>

<template>
  <Head title="Polling units" />

  <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h1 class="text-xl font-black text-neutral-900">Polling units</h1>
    <p class="mt-1 text-sm text-neutral-600">
      <b class="text-neutral-900">{{ scopeName }}</b> ward. Open a unit to do what is missing, for example if its agent has not done it.
    </p>

    <div class="mt-4 space-y-3">
      <OfficeSelect v-if="offices.length > 1" v-model="office" :offices="offices" />
      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
        <input
          v-model="search" type="search" placeholder="Search by name or code"
          class="h-11 w-full rounded-lg border border-[#e5e7eb] bg-white pl-9 pr-3 text-base text-[#111827] outline-none [-webkit-text-fill-color:#111827] placeholder:text-[#9ca3af] focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/30"
        />
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="t in tabs" :key="t.value" type="button"
          class="rounded-full border px-3.5 py-2 text-sm font-bold transition"
          :class="status === t.value ? 'border-red-600 bg-red-600 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'"
          @click="status = t.value"
        >{{ t.label }} <span class="opacity-70">{{ t.count() }}</span></button>
      </div>
    </div>
  </section>

  <p v-if="!pus.data.length" class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center text-sm text-neutral-500">No polling unit found.</p>

  <ul class="space-y-3">
    <li v-for="pu in pus.data" :key="pu.id">
      <Link
        :href="route('portal.pus.show', { pu: pu.id, office })"
        class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm transition hover:border-red-600"
      >
        <div class="min-w-0 flex-1">
          <p class="truncate text-base font-bold text-neutral-900">{{ pu.name }}</p>
          <p class="text-xs font-medium text-neutral-500"><span class="rounded bg-neutral-100 px-1.5 py-0.5">{{ pu.code }}</span></p>
          <div class="mt-2 flex flex-wrap gap-1.5">
            <TaskStatus :done="pu.officials_done" done-text="Names" pending-text="Names" />
            <TaskStatus :done="pu.sheet_count > 0" done-text="Sheet" pending-text="Sheet" />
            <TaskStatus :done="pu.has_video" done-text="Video" pending-text="Video" />
            <TaskStatus :done="pu.has_figures" done-text="Figures" pending-text="Figures" />
          </div>
        </div>
        <span class="shrink-0 text-sm font-black" :class="pu.steps_done === 4 ? 'text-neutral-900' : 'text-red-700'">{{ pu.steps_done }}/4</span>
        <ChevronRight class="h-5 w-5 shrink-0 text-neutral-400" />
      </Link>
    </li>
  </ul>

  <Pagination
    v-if="pus.meta.last_page > 1"
    :current-page="pus.meta.current_page" :last-page="pus.meta.last_page" :total="pus.meta.total"
    :per-page="pus.meta.per_page" :from="pus.meta.from" :to="pus.meta.to" @change="visit"
  />
</template>
