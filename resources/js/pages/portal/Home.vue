<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { ArrowRight, MapPin, Users2 } from 'lucide-vue-next'
import TaskSteps from '@/components/pu/TaskSteps.vue'
import OfficeSelect from '@/components/pu/OfficeSelect.vue'

const props = defineProps<{
  profile: { name: string; application_id: string | null; location: Record<string, string> }
  team: { id: string; name: string } | null
  ledTeams: { id: string; name: string }[]
  tasks: { total: number; done: number; new: number; progress: number } | null
  unit: { id: string; name: string; code: string; ward: string | null; lga: string | null; officials_done: boolean; has_video: boolean; sheet_count: number; has_figures: boolean } | null
  office: string
  offices: { value: string; label: string }[]
  taskAccess: boolean
  jurisdictionLevel: string | null
}>()

const selectedOffice = ref(props.office)
watch(selectedOffice, (office) => router.get(route('portal.home'), { office }, { preserveState: true, preserveScroll: true, replace: true }))

const LABEL: Record<string, string> = { state: 'State', zone: 'Zone', lga: 'LGA', ward: 'Ward', pu: 'Polling unit' }
const pct = (n: number) => (props.tasks?.total ? Math.round((n / props.tasks.total) * 100) : 0)
const stepsDone = () => (props.unit ? Number(props.unit.officials_done) + Number(props.unit.has_video) + Number(props.unit.sheet_count > 0) + Number(props.unit.has_figures) : 0)
</script>

<template>
  <Head title="Dashboard" />

  <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
    <div class="flex h-1.5"><span class="flex-1 bg-red-600" /><span class="flex-1 bg-white" /><span class="flex-1 bg-neutral-900" /></div>
    <div class="p-5">
      <h1 class="text-xl font-black text-neutral-900">Welcome, {{ profile.name }}</h1>
      <p v-if="profile.application_id" class="mt-1 text-xs font-medium text-neutral-400">ID {{ profile.application_id }}</p>

      <dl v-if="Object.keys(profile.location).length" class="mt-4 grid grid-cols-2 gap-3 text-sm">
        <div v-for="(name, level) in profile.location" :key="level">
          <dt class="flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wide text-neutral-400"><MapPin class="h-3 w-3" />{{ LABEL[level] ?? level }}</dt>
          <dd class="font-medium text-neutral-800">{{ name }}</dd>
        </div>
      </dl>
      <p v-else class="mt-3 text-sm text-amber-700">No location has been given to your account yet.</p>
    </div>
  </section>

  <!-- Polling-unit agent: only his own unit -->
  <template v-if="unit">
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <OfficeSelect v-if="offices.length > 1" v-model="selectedOffice" :offices="offices" />
    </div>
    <div>
      <h2 class="text-base font-black text-neutral-900">My polling unit: {{ unit.name }}</h2>
      <p class="mt-0.5 text-sm font-bold" :class="stepsDone() === 4 ? 'text-neutral-900' : 'text-red-700'">
        {{ stepsDone() === 4 ? 'All done. Thank you!' : `${stepsDone()} of 4 tasks done` }}
      </p>
    </div>
    <TaskSteps :unit="unit" :office="selectedOffice" />
  </template>

  <!-- Ward agent: progress of the whole ward -->
  <section v-if="tasks" class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <OfficeSelect v-if="offices.length > 1" v-model="selectedOffice" :offices="offices" />
    <div>
      <h2 class="text-base font-black text-neutral-900">My ward</h2>
      <p class="text-sm text-neutral-600">{{ tasks.total }} polling unit{{ tasks.total === 1 ? '' : 's' }} in your ward</p>
      <p class="mt-1 text-xs text-neutral-500">You can open any unit to enter or review its officials and result evidence, even when its assigned agent has not completed the work.</p>
    </div>

    <div class="h-3 overflow-hidden rounded-full bg-neutral-200">
      <div class="h-full rounded-full bg-neutral-900 transition-all" :style="{ width: pct(tasks.done) + '%' }"></div>
    </div>

    <div class="grid grid-cols-3 gap-2 text-center">
      <div class="rounded-xl bg-neutral-900 p-3 text-white"><p class="text-2xl font-black">{{ tasks.done }}</p><p class="text-xs font-bold">Finished</p></div>
      <div class="rounded-xl bg-red-600 p-3 text-white"><p class="text-2xl font-black">{{ tasks.progress }}</p><p class="text-xs font-bold">Not finished</p></div>
      <div class="rounded-xl bg-neutral-100 p-3 text-neutral-900"><p class="text-2xl font-black">{{ tasks.new }}</p><p class="text-xs font-bold">Not started</p></div>
    </div>

    <Link :href="route('portal.pus', { office: selectedOffice })" class="flex min-h-12 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 text-base font-black text-white transition hover:bg-red-700">
      Open polling units <ArrowRight class="h-5 w-5" />
    </Link>
  </section>

  <section v-if="taskAccess && !unit && !tasks" class="rounded-2xl border border-dashed border-amber-300 bg-amber-50 p-5">
    <h2 class="font-black text-amber-900">No polling-unit assignment found</h2>
    <p class="mt-1 text-sm text-amber-800">Your account has field-task access, but no {{ jurisdictionLevel === 'pu' ? 'polling unit' : 'ward' }} is assigned to it yet. Please contact your administrator to update your location.</p>
  </section>

  <section v-if="team || ledTeams.length" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h3 class="flex items-center gap-2 text-sm font-bold text-neutral-900"><Users2 class="h-4 w-4 text-red-600" />My team</h3>
    <ul class="mt-3 space-y-2 text-sm">
      <li v-if="team" class="text-neutral-700">{{ team.name }}</li>
      <li v-for="t in ledTeams" :key="t.id">
        <Link :href="route('campaign-teams.show', t.id)" class="font-medium text-red-700 hover:underline">{{ t.name }} (you lead)</Link>
      </li>
    </ul>
  </section>
</template>
