<script setup lang="ts">
/**
 * Aspirant dashboard: a read-only progress report for the whole campaign.
 * Nothing here creates, edits or deletes records.
 */
import AgentDeployment from '@/components/dashboard/AgentDeployment.vue'
import MembershipChart from '@/components/dashboard/MembershipChart.vue'
import StatCard from '@/components/dashboard/StatCard.vue'
import Button from '@/components/ui/Button.vue'
import { usePermissions } from '@/composables/usePermissions'
import { CheckCircle2, Download, Search, Target, TrendingUp, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'

interface Trend { total: number; last30: number; last7: number }
interface LgaRow { id: string; name: string; people: number; results: number; verified: number }

const props = defineProps<{
  aspirant: { name: string; title: string; state: string | null; vision: string | null; imageUrl: string | null; overallProgress: number | null; totalSupporters: number | null; supportersGrowthThisWeek: number | null; campaignTeamMembers: number } | null
  stats: { members: Trend; partyAgents: Trend; coordinators: Trend; stakeholders: Trend; pollingUnits: number; registeredVoters: number }
  results: { total: number; verified: number; pending: number; disputed: number; verifiedPct: number; puReporting: number; puTotal: number; puReportingPct: number }
  agentDeployment: any
  membershipByLga: { lga: string; count: number }[]
  lgaProgress: LgaRow[]
  generatedAt: string
}>()

const { auth } = usePermissions()
const fmt = (n: number | null | undefined) => (n ?? 0).toLocaleString()
const clamp = (n: number | null | undefined) => Math.min(100, Math.max(0, Math.round(n ?? 0)))
const updated = computed(() => new Date(props.generatedAt).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' }))

/* LGA progress table: search + sort, scrolls sideways on small screens */
const search = ref('')
const sortKey = ref<'name' | 'people' | 'results' | 'verified'>('people')
const sortDir = ref<'asc' | 'desc'>('desc')
const maxPeople = computed(() => Math.max(1, ...props.lgaProgress.map((r) => r.people)))
const rows = computed(() => {
  const q = search.value.trim().toLowerCase()
  const list = props.lgaProgress.filter((r) => !q || r.name.toLowerCase().includes(q))
  const dir = sortDir.value === 'asc' ? 1 : -1
  return [...list].sort((a, b) => (sortKey.value === 'name' ? a.name.localeCompare(b.name) : a[sortKey.value] - b[sortKey.value]) * dir)
})
function sortBy(key: typeof sortKey.value) {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = key === 'name' ? 'asc' : 'desc' }
}
const verifiedPct = (r: LgaRow) => (r.results ? Math.round((r.verified / r.results) * 100) : 0)
const card = 'rounded-2xl border border-gray-100 bg-white p-5 shadow-sm'
const heading = 'text-[11px] font-black uppercase tracking-[0.18em] text-gray-400'
</script>

<template>
  <div class="space-y-6 sm:space-y-8">
    <section class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
      <div class="min-w-0">
        <h2 class="text-lg font-black text-gray-900 sm:text-xl">Campaign progress report</h2>
        <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">Welcome, {{ auth.user?.first_name }} · Updated {{ updated }}</p>
      </div>
      <Button :href="route('dashboard.export')" external class="w-full sm:w-auto">
        <Download class="h-4 w-4 shrink-0" /> Download report
      </Button>
    </section>

    <!-- Headline: candidate + overall progress -->
    <section :class="[card, 'grid gap-5 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:items-center']">
      <div class="flex min-w-0 items-center gap-4">
        <img v-if="aspirant?.imageUrl" :src="aspirant.imageUrl" :alt="aspirant.name" class="h-24 w-20 shrink-0 rounded-2xl object-cover object-top shadow-md sm:h-32 sm:w-28 lg:h-40 lg:w-32" />
        <div v-else class="flex h-24 w-20 shrink-0 items-center justify-center rounded-2xl bg-green-50 text-green-700 sm:h-32 sm:w-28 lg:h-40 lg:w-32"><Target class="h-7 w-7" /></div>
        <div class="min-w-0">
          <p :class="heading">{{ aspirant?.title ?? 'Governorship candidate' }}</p>
          <h3 class="mt-1 truncate text-lg font-black text-gray-900 sm:text-xl">{{ aspirant?.name ?? 'Candidate profile pending' }}</h3>
          <p v-if="aspirant?.vision" class="mt-1 line-clamp-2 text-sm text-gray-500">{{ aspirant.vision }}</p>
        </div>
      </div>
      <div class="space-y-4">
        <div>
          <div class="mb-1.5 flex items-center justify-between text-sm"><span class="font-semibold text-gray-500">Overall campaign progress</span><span class="font-black text-gray-900">{{ clamp(aspirant?.overallProgress) }}%</span></div>
          <div class="h-2.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-green-600 transition-all duration-500" :style="{ width: clamp(aspirant?.overallProgress) + '%' }" /></div>
        </div>
        <dl class="grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-xl bg-gray-50 p-3"><dt class="flex items-center gap-1.5 text-xs font-medium text-gray-500"><Users class="h-3.5 w-3.5" />Supporters</dt><dd class="mt-1 text-lg font-black text-gray-900">{{ fmt(aspirant?.totalSupporters) }}</dd><p class="text-[11px] font-medium text-green-700">+{{ fmt(aspirant?.supportersGrowthThisWeek) }} this week</p></div>
          <div class="rounded-xl bg-gray-50 p-3"><dt class="flex items-center gap-1.5 text-xs font-medium text-gray-500"><TrendingUp class="h-3.5 w-3.5" />Campaign team</dt><dd class="mt-1 text-lg font-black text-gray-900">{{ fmt(aspirant?.campaignTeamMembers) }}</dd><p class="text-[11px] text-gray-500">Team members</p></div>
        </dl>
      </div>
    </section>

    <!-- Reach -->
    <section class="space-y-3" aria-labelledby="reach">
      <h3 id="reach" :class="heading">Reach &amp; organisation</h3>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 2xl:grid-cols-6">
        <StatCard :value="stats.members.total" label="Members" icon="Users" :subtitle="`+${fmt(stats.members.last30)} in 30 days`" icon-bg="bg-green-100" icon-color="text-green-700" />
        <StatCard :value="stats.partyAgents.total" label="Party Agents" icon="UserCheck" :subtitle="`+${fmt(stats.partyAgents.last30)} in 30 days`" icon-bg="bg-amber-100" icon-color="text-amber-700" />
        <StatCard :value="stats.coordinators.total" label="Coordinators" icon="Briefcase" :subtitle="`+${fmt(stats.coordinators.last30)} in 30 days`" icon-bg="bg-gray-100" icon-color="text-gray-700" />
        <StatCard :value="stats.stakeholders.total" label="Stakeholders" icon="Handshake" :subtitle="`+${fmt(stats.stakeholders.last30)} in 30 days`" icon-bg="bg-green-100" icon-color="text-green-700" />
        <StatCard :value="stats.pollingUnits" label="Polling Units" icon="BarChart3" icon-bg="bg-amber-100" icon-color="text-amber-700" />
        <StatCard :value="stats.registeredVoters" label="Registered Voters" icon="Users" icon-bg="bg-gray-100" icon-color="text-gray-700" />
      </div>
    </section>

    <!-- Election-day readiness -->
    <section class="space-y-3" aria-labelledby="ready">
      <h3 id="ready" :class="heading">Results &amp; readiness</h3>
      <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-3">
        <div :class="card">
          <h4 class="text-sm font-bold text-gray-900">Result verification</h4>
          <p class="mt-1 text-xs text-gray-500">{{ fmt(results.total) }} results uploaded</p>
          <div class="mt-4 flex h-2.5 overflow-hidden rounded-full bg-gray-100">
            <div class="bg-green-500" :style="{ width: (results.total ? (results.verified / results.total) * 100 : 0) + '%' }" />
            <div class="bg-amber-400" :style="{ width: (results.total ? (results.pending / results.total) * 100 : 0) + '%' }" />
            <div class="bg-red-500" :style="{ width: (results.total ? (results.disputed / results.total) * 100 : 0) + '%' }" />
          </div>
          <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="flex items-center gap-2 text-gray-600"><span class="h-2 w-2 rounded-full bg-green-500" />Verified</dt><dd class="font-semibold">{{ fmt(results.verified) }}</dd></div>
            <div class="flex justify-between"><dt class="flex items-center gap-2 text-gray-600"><span class="h-2 w-2 rounded-full bg-amber-400" />Pending</dt><dd class="font-semibold">{{ fmt(results.pending) }}</dd></div>
            <div class="flex justify-between"><dt class="flex items-center gap-2 text-gray-600"><span class="h-2 w-2 rounded-full bg-red-500" />Disputed</dt><dd class="font-semibold">{{ fmt(results.disputed) }}</dd></div>
          </dl>
        </div>
        <div :class="card">
          <h4 class="text-sm font-bold text-gray-900">Polling unit coverage</h4>
          <p class="mt-1 text-xs text-gray-500">Units that have reported a result</p>
          <p class="mt-4 text-3xl font-black text-gray-900">{{ results.puReportingPct }}<span class="text-lg text-gray-400">%</span></p>
          <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-green-600" :style="{ width: clamp(results.puReportingPct) + '%' }" /></div>
          <p class="mt-3 flex items-center gap-1.5 text-xs font-medium text-gray-500"><CheckCircle2 class="h-3.5 w-3.5 text-green-600" />{{ fmt(results.puReporting) }} of {{ fmt(results.puTotal) }} polling units</p>
        </div>
        <AgentDeployment :deployment="agentDeployment" />
      </div>
    </section>

    <section class="space-y-3" aria-labelledby="geo">
      <h3 id="geo" :class="heading">Progress by local government</h3>
      <div class="grid grid-cols-1 gap-4 sm:gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
        <MembershipChart :data="membershipByLga" />

        <div class="min-w-0 rounded-2xl border border-gray-100 bg-white shadow-sm">
          <div class="flex flex-col gap-3 border-b border-gray-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <h4 class="text-sm font-bold text-gray-900">LGA scorecard</h4>
            <label class="relative block w-full sm:w-56">
              <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input v-model="search" type="search" placeholder="Search LGA…" aria-label="Search LGA" class="h-10 w-full rounded-xl border border-gray-200 bg-white pr-3 pl-9 text-sm outline-none focus:border-green-600 focus:ring-2 focus:ring-green-600/20" />
            </label>
          </div>
          <div class="max-h-[22rem] overflow-auto">
            <table class="w-full min-w-[34rem] text-left text-sm">
              <thead class="sticky top-0 z-10 bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                  <th v-for="c in [{ k: 'name', l: 'LGA' }, { k: 'people', l: 'People' }, { k: 'results', l: 'Results' }, { k: 'verified', l: 'Verified' }]" :key="c.k" scope="col" class="px-4 py-2.5 whitespace-nowrap" :aria-sort="sortKey === c.k ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                    <button type="button" class="inline-flex items-center gap-1 hover:text-green-800" @click="sortBy(c.k as any)">{{ c.l }}<span v-if="sortKey === c.k" aria-hidden="true">{{ sortDir === 'asc' ? '↑' : '↓' }}</span></button>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="r in rows" :key="r.id" class="hover:bg-green-50/30">
                  <td class="px-4 py-2.5 font-medium whitespace-nowrap text-gray-900">{{ r.name }}</td>
                  <td class="px-4 py-2.5">
                    <div class="flex items-center gap-2"><span class="w-12 font-semibold tabular-nums">{{ fmt(r.people) }}</span><div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-green-600" :style="{ width: (r.people / maxPeople) * 100 + '%' }" /></div></div>
                  </td>
                  <td class="px-4 py-2.5 tabular-nums text-gray-700">{{ fmt(r.results) }}</td>
                  <td class="px-4 py-2.5 tabular-nums text-gray-700">{{ fmt(r.verified) }} <span class="text-xs text-gray-400">({{ verifiedPct(r) }}%)</span></td>
                </tr>
                <tr v-if="!rows.length"><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">No local government matches your search.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
