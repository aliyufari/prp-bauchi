<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ClipboardList, Search, Video } from 'lucide-vue-next'
import TaskStatus from '@/components/pu/TaskStatus.vue'
import Pagination from '@/components/ui/Pagination.vue'
import OfficeSelect from '@/components/pu/OfficeSelect.vue'

interface PuRow {
  id: string; name: string; code: string; ward: string | null
  officials_done: boolean; has_video: boolean; sheet_count: number; result_done: boolean
}
const props = defineProps<{
  scope: 'pu' | 'ward'
  scopeName: string | null
  pus: { data: PuRow[]; meta: { current_page: number; last_page: number; total: number; per_page: number; from: number | null; to: number | null } }
  filters: { search?: string; status?: string }
  office: string
  offices: { value: string; label: string }[]
}>()

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const office = ref(props.office)
let timer: ReturnType<typeof setTimeout>
function visit(page = 1) {
  router.get(route('portal.pus'), { office: office.value, search: search.value || undefined, status: status.value || undefined, page: page > 1 ? page : undefined }, { preserveState: true, replace: true })
}
watch(search, () => { clearTimeout(timer); timer = setTimeout(() => visit(), 350) })
watch(status, () => visit())
watch(office, () => visit())

const isWard = computed(() => props.scope === 'ward')
const tabs = [
  { value: '', label: 'All' },
  { value: 'officials', label: 'No officials' },
  { value: 'result', label: 'No result' },
]
</script>

<template>
  <Head title="My Polling Units" />

  <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h1 class="text-lg font-black text-neutral-900">{{ isWard ? 'Polling units in your ward' : 'Your polling unit' }}</h1>
    <p class="mt-1 text-sm text-neutral-600">
      <template v-if="isWard">
        You can add officials and upload results for every polling unit in <b class="text-neutral-900">{{ scopeName }}</b>,
        for example when the unit's own agent has not done it.
      </template>
      <template v-else>Complete the two tasks below for <b class="text-neutral-900">{{ scopeName }}</b>.</template>
    </p>

    <div class="mt-4"><OfficeSelect v-model="office" :offices="offices" /></div>

    <div v-if="isWard" class="mt-4 space-y-3">
      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
        <input
          v-model="search" type="search" placeholder="Search polling unit name or code"
          class="h-10 w-full rounded-lg border border-[#e5e7eb] bg-white pl-9 pr-3 text-sm text-[#111827] outline-none [-webkit-text-fill-color:#111827] placeholder:text-[#9ca3af] focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/30"
        />
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="t in tabs" :key="t.value" type="button"
          class="rounded-full border px-3 py-1.5 text-xs font-bold transition"
          :class="status === t.value ? 'border-red-600 bg-red-600 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'"
          @click="status = t.value"
        >{{ t.label }}</button>
      </div>
    </div>
  </section>

  <p v-if="!pus.data.length" class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center text-sm text-neutral-500">
    {{ isWard ? 'No polling units match.' : 'No polling unit is assigned to your account yet. Contact your coordinator.' }}
  </p>

  <ul class="space-y-3">
    <li v-for="pu in pus.data" :key="pu.id" class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
          <p class="truncate text-base font-bold text-neutral-900">{{ pu.name }}</p>
          <p class="text-xs font-medium text-neutral-500">
            <span class="rounded bg-neutral-100 px-1.5 py-0.5">{{ pu.code }}</span>
            <span v-if="pu.ward"> · {{ pu.ward }}</span>
          </p>
        </div>
        <div class="flex flex-wrap gap-1.5">
          <TaskStatus :done="pu.officials_done" done-text="Officials added" pending-text="No officials" />
          <TaskStatus :done="pu.result_done" done-text="Result uploaded" :pending-text="pu.has_video || pu.sheet_count ? 'Result incomplete' : 'No result'" />
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
        <Link
          :href="route('portal.pus.officials', pu.id)"
          class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-bold text-neutral-900 transition hover:border-red-600 hover:text-red-700"
        >
          <ClipboardList class="h-5 w-5 text-red-600" />
          {{ pu.officials_done ? 'Edit officials' : 'Add officials' }}
        </Link>
        <Link
          :href="route('portal.pus.result', { pu: pu.id, office })"
          class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 text-sm font-bold text-white transition hover:bg-red-700"
        >
          <Video class="h-5 w-5" />
          {{ pu.result_done ? 'View / update result' : 'Upload result' }}
        </Link>
      </div>
    </li>
  </ul>

  <Pagination
    v-if="pus.meta.last_page > 1"
    :current-page="pus.meta.current_page" :last-page="pus.meta.last_page" :total="pus.meta.total"
    :per-page="pus.meta.per_page" :from="pus.meta.from" :to="pus.meta.to" @change="visit"
  />
</template>
