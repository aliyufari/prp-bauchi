<script setup lang="ts">
import OfficeSelect from '@/components/pu/OfficeSelect.vue'
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { ChevronDown, Search } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import StatCard from '@/components/dashboard/StatCard.vue'
import TaskStatus from '@/components/pu/TaskStatus.vue'
import Pagination from '@/components/ui/Pagination.vue'

interface Sheet { index: number; name: string; url: string }
interface Row {
  id: string; name: string; code: string; ward: string | null; lga: string | null
  officials_done: boolean; has_video: boolean; sheet_count: number; has_figures: boolean; result_done: boolean
  submitted_by: string | null; submitted_at: string | null
  activity: { action: string; performed_by: string | null; created_at: string; details: Record<string, unknown> | null }[]
  official: { po_name: string; apo1_name: string; apo2_name: string; security_name: string } | null
  submission: { video: { url: string } | null; sheets: Sheet[]; remarks: string | null } | null
}
const props = defineProps<{
  pus: { data: Row[]; meta: { current_page: number; last_page: number; total: number; per_page: number; from: number | null; to: number | null } }
  stats: { total: number; officials: number; results: number }
  filters: { search?: string; status?: string }
  office: string
  offices: { value: string; label: string }[]
}>()

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const office = ref(props.office)
let timer: ReturnType<typeof setTimeout>
function visit(page = 1) {
  router.get(route('pu-reports.index'), { office: office.value, search: search.value || undefined, status: status.value || undefined, page: page > 1 ? page : undefined }, { preserveState: true, replace: true })
}
watch(search, () => { clearTimeout(timer); timer = setTimeout(() => visit(), 350) })
watch(status, () => visit())
watch(office, () => visit())

const tabs = [
  { value: '', label: 'All' },
  { value: 'complete', label: 'Complete' },
  { value: 'officials', label: 'No officials' },
  { value: 'result', label: 'No result' },
]
const when = (iso: string | null) => (iso ? new Date(iso).toLocaleString() : '')
const ACTION_LABELS: Record<string, string> = {
  officials_recorded: 'Officials recorded',
  officials_updated: 'Officials updated',
  result_sheets_uploaded: 'Result-sheet photos uploaded',
  counting_video_uploaded: 'Counting video uploaded',
  counting_video_deleted: 'Counting video removed',
  result_sheet_deleted: 'Result-sheet photo removed',
  party_figures_recorded: 'Party figures entered',
  party_figures_updated: 'Party figures updated',
}
const actionLabel = (action: string) => ACTION_LABELS[action] ?? action.replaceAll('_', ' ')
const LABELS: [keyof NonNullable<Row['official']>, string][] = [
  ['po_name', 'PO'], ['apo1_name', 'APO 1'], ['apo2_name', 'APO 2'], ['security_name', 'Security'],
]
</script>

<template>
  <Head title="PU Reports" />

  <AppLayout>
    <div class="space-y-4 sm:space-y-6">
      <div>
        <h2 class="text-lg font-black text-gray-900 sm:text-xl">PU Reports</h2>
        <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">Officials and result evidence submitted by party agents.</p>
      </div>

      <OfficeSelect v-model="office" :offices="offices" />

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <StatCard :value="stats.total" label="Polling units" icon="MapPin" />
        <StatCard :value="stats.officials" label="With officials" icon="ClipboardList" />
        <StatCard :value="stats.results" label="With full result evidence" icon="FileBarChart" />
      </div>

      <div class="space-y-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
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

      <p v-if="!pus.data.length" class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">No polling units match.</p>

      <ul class="space-y-3">
        <li v-for="pu in pus.data" :key="pu.id" class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
          <details class="group">
            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 p-4">
              <div class="min-w-0">
                <p class="truncate font-bold text-gray-900">{{ pu.name }}</p>
                <p class="text-xs font-medium text-gray-500">{{ pu.code }}<span v-if="pu.ward"> · {{ pu.ward }}</span><span v-if="pu.lga"> · {{ pu.lga }}</span></p>
              </div>
              <div class="flex items-center gap-2">
                <TaskStatus :done="pu.officials_done" done-text="Officials" pending-text="No officials" />
                <TaskStatus :done="pu.result_done" done-text="Evidence" :pending-text="pu.has_video || pu.sheet_count ? 'Incomplete evidence' : 'No evidence'" />
                <TaskStatus :done="pu.has_figures" done-text="Figures" pending-text="No figures" />
                <ChevronDown class="h-4 w-4 text-gray-400 transition group-open:rotate-180" />
              </div>
            </summary>

            <div class="space-y-4 border-t border-gray-100 p-4">
              <dl v-if="pu.official" class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div v-for="[key, label] in LABELS" :key="key">
                  <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ label }}</dt>
                  <dd class="font-medium text-gray-800">{{ pu.official[key] }}</dd>
                </div>
              </dl>
              <p v-else class="text-sm text-gray-500">Officials have not been added.</p>

              <div v-if="pu.submission">
                <video v-if="pu.submission.video" :src="pu.submission.video.url" controls playsinline preload="none" class="aspect-video w-full max-w-xl rounded-xl bg-black" />
                <div v-if="pu.submission.sheets.length" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-6">
                  <a v-for="s in pu.submission.sheets" :key="s.index" :href="s.url" target="_blank" rel="noopener" class="overflow-hidden rounded-lg border border-gray-200">
                    <img :src="s.url" :alt="s.name" loading="lazy" class="aspect-[3/4] w-full object-cover" />
                  </a>
                </div>
                <p v-if="pu.submission.remarks" class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">{{ pu.submission.remarks }}</p>
                <p class="mt-3 text-xs text-gray-500">Submitted by {{ pu.submitted_by ?? 'unknown' }} · {{ when(pu.submitted_at) }}</p>
              </div>
              <p v-else class="text-sm text-gray-500">No result evidence uploaded yet.</p>

              <details v-if="pu.activity.length" class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                <summary class="cursor-pointer text-sm font-bold text-gray-800">Recent activity ({{ pu.activity.length }})</summary>
                <ol class="mt-3 space-y-2">
                  <li v-for="(event, index) in pu.activity" :key="`${event.action}-${event.created_at}-${index}`" class="flex flex-wrap justify-between gap-2 text-xs">
                    <span class="font-semibold text-gray-800">{{ actionLabel(event.action) }} <span class="font-normal text-gray-500">by {{ event.performed_by ?? 'unknown' }}</span></span>
                    <time class="text-gray-500">{{ when(event.created_at) }}</time>
                  </li>
                </ol>
              </details>
            </div>
          </details>
        </li>
      </ul>

      <Pagination
        v-if="pus.meta.last_page > 1"
        :current-page="pus.meta.current_page" :last-page="pus.meta.last_page" :total="pus.meta.total"
        :per-page="pus.meta.per_page" :from="pus.meta.from" :to="pus.meta.to" @change="visit"
      />
    </div>
  </AppLayout>
</template>
