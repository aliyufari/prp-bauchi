<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowRight, ClipboardList, MapPin, Users2, Video } from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'
import OfficeSelect from '@/components/pu/OfficeSelect.vue'

const props = defineProps<{
  profile: { name: string; application_id: string | null; location: Record<string, string> }
  team: { id: string; name: string } | null
  ledTeams: { id: string; name: string }[]
  tasks: { scope: 'pu' | 'ward'; total: number; officials: number; results: number; outstanding: number } | null
  office: string
  offices: { value: string; label: string }[]
}>()

const office = ref(props.office)
watch(office, (o) => router.get(route('portal.home'), { office: o }, { preserveState: true, replace: true }))
const pct = (n: number) => (props.tasks && props.tasks.total ? Math.round((n / props.tasks.total) * 100) : 0)

const { can } = usePermissions()
const LABEL: Record<string, string> = { state: 'State', zone: 'Zone', lga: 'LGA', ward: 'Ward', pu: 'Polling unit' }
</script>

<template>
  <Head title="Dashboard" />

  <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-black text-gray-900">Welcome, {{ profile.name }}</h2>
    <p v-if="profile.application_id" class="mt-1 text-xs font-medium text-gray-400">ID {{ profile.application_id }}</p>

    <dl v-if="Object.keys(profile.location).length" class="mt-4 grid grid-cols-2 gap-3 text-sm">
      <div v-for="(name, level) in profile.location" :key="level">
        <dt class="flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
          <MapPin class="h-3 w-3" />{{ LABEL[level] ?? level }}
        </dt>
        <dd class="font-medium text-gray-800">{{ name }}</dd>
      </div>
    </dl>
    <p v-else class="mt-3 text-sm text-amber-700">No location has been assigned to your account yet.</p>
  </section>

  <section v-if="tasks" class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h3 class="text-base font-black text-neutral-900">{{ tasks.scope === 'ward' ? 'Ward progress' : 'My task progress' }}</h3>
        <p class="text-xs text-neutral-500">{{ tasks.total }} polling unit{{ tasks.total === 1 ? '' : 's' }} in your {{ tasks.scope === 'ward' ? 'ward' : 'scope' }}</p>
      </div>
      <OfficeSelect v-model="office" :offices="offices" />
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div class="rounded-xl bg-neutral-50 p-4">
        <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-neutral-500"><ClipboardList class="h-4 w-4 text-red-600" />Officials added</p>
        <p class="mt-1 text-2xl font-black text-neutral-900">{{ tasks.officials }}<span class="text-sm font-semibold text-neutral-400"> / {{ tasks.total }}</span></p>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-neutral-200"><div class="h-full rounded-full bg-red-600" :style="{ width: pct(tasks.officials) + '%' }"></div></div>
      </div>
      <div class="rounded-xl bg-neutral-50 p-4">
        <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-neutral-500"><Video class="h-4 w-4 text-red-600" />Result evidence</p>
        <p class="mt-1 text-2xl font-black text-neutral-900">{{ tasks.results }}<span class="text-sm font-semibold text-neutral-400"> / {{ tasks.total }}</span></p>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-neutral-200"><div class="h-full rounded-full bg-neutral-900" :style="{ width: pct(tasks.results) + '%' }"></div></div>
      </div>
    </div>
    <p v-if="tasks.outstanding" class="text-sm font-medium text-red-700">{{ tasks.outstanding }} polling unit{{ tasks.outstanding === 1 ? '' : 's' }} still without full result evidence.</p>
    <p v-else-if="tasks.total" class="text-sm font-medium text-neutral-700">All result evidence is uploaded.</p>
  </section>

  <Link
    v-if="can('pu-tasks.manage')"
    :href="route('portal.pus', { office })"
    class="group block overflow-hidden rounded-2xl bg-gradient-to-br from-red-600 to-red-800 p-5 text-white shadow-sm transition hover:shadow-md"
  >
    <h3 class="flex items-center gap-2 text-base font-black"><ClipboardList class="h-5 w-5" />Polling unit tasks</h3>
    <p class="mt-1 text-sm text-white/85">Add the PO, APO 1, APO 2 and security names, then upload the counting video and result sheet photos.</p>
    <span class="mt-3 inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-red-700">Open my polling units <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" /></span>
  </Link>

  <section v-if="team || ledTeams.length" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <h3 class="flex items-center gap-2 text-sm font-bold text-gray-900"><Users2 class="h-4 w-4 text-red-700" />Your team</h3>
    <ul class="mt-3 space-y-2 text-sm">
      <li v-if="team" class="text-gray-700">{{ team.name }}</li>
      <li v-for="t in ledTeams" :key="t.id">
        <Link :href="route('campaign-teams.show', t.id)" class="font-medium text-red-700 hover:underline">{{ t.name }} (you lead)</Link>
      </li>
    </ul>
  </section>
</template>