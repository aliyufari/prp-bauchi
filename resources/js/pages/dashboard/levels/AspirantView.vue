<script setup lang="ts">
/**
 * Aspirant dashboard: a read-only progress report for the whole campaign.
 * Nothing here creates, edits or deletes records.
 */
import AgentDeployment from '@/components/dashboard/AgentDeployment.vue'
import MembershipChart from '@/components/dashboard/MembershipChart.vue'
import AspirantHero from '@/components/aspirant/AspirantHero.vue'
import KpiTile from '@/components/aspirant/KpiTile.vue'
import LgaLeaderboard from '@/components/aspirant/LgaLeaderboard.vue'
import TrendChart from '@/components/aspirant/TrendChart.vue'
import { CheckCircle2, Search } from 'lucide-vue-next'
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
  trend: { labels: string[]; members: number[]; agents: number[]; leaders: number[] }
  readiness: { score: number; parts: { label: string; value: number }[] }
  coverage: { lgasCovered: number; totalLgas: number }
}>()

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
const seg = (n: number) => `${props.results.total ? Math.min(100, Math.max(0, (n / props.results.total) * 100)) : 0}%`
const verifiedPct = (r: LgaRow) => (r.results ? Math.round((r.verified / r.results) * 100) : 0)
const spark = (k: 'members' | 'agents' | 'leaders') => props.trend?.[k] ?? []
const card = 'rounded-2xl border border-gray-100 bg-white p-5 shadow-sm'
const heading = 'text-[11px] font-black uppercase tracking-[0.18em] text-gray-400'
</script>

<template>
  <div class="space-y-6 sm:space-y-8">
    <AspirantHero
      :aspirant="aspirant"
      :readiness="readiness"
      :coverage="coverage"
      :members="stats.members.total"
      :export-href="route('dashboard.export')"
      :updated="updated"
    />

    <!-- Reach -->
    <section class="space-y-3" aria-labelledby="reach">
      <h3 id="reach" :class="heading">Reach &amp; organisation</h3>
      <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <KpiTile label="Members" :value="stats.members.total" :delta="stats.members.last30" :series="spark('members')" tone="green" />
        <KpiTile label="Party agents" :value="stats.partyAgents.total" :delta="stats.partyAgents.last30" :series="spark('agents')" tone="gold" />
        <KpiTile label="Coordinators" :value="stats.coordinators.total" :delta="stats.coordinators.last30" :series="spark('leaders')" tone="teal" />
        <KpiTile label="Stakeholders" :value="stats.stakeholders.total" :delta="stats.stakeholders.last30" tone="slate" />
      </div>
      <div class="grid grid-cols-2 gap-3 sm:gap-4">
        <KpiTile label="Polling units" :value="stats.pollingUnits" tone="green" />
        <KpiTile label="Registered voters" :value="stats.registeredVoters" tone="gold" />
      </div>
    </section>

    <!-- Momentum + where to focus -->
    <section class="space-y-3" aria-labelledby="momentum">
      <h3 id="momentum" :class="heading">Momentum</h3>
      <div class="grid grid-cols-1 gap-4 sm:gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <TrendChart :trend="trend" />
        <LgaLeaderboard :rows="lgaProgress" />
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
            <div class="bg-green-500" :style="{ width: seg(results.verified) }" />
            <div class="bg-amber-400" :style="{ width: seg(results.pending) }" />
            <div class="bg-red-500" :style="{ width: seg(results.disputed) }" />
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
              <input v-model="search" type="search" placeholder="Search LGA…" aria-label="Search LGA" class="h-10 w-full rounded-xl border border-gray-200 bg-white pr-3 pl-9 text-sm text-[#111827] [color-scheme:light] outline-none focus:border-green-600 focus:ring-2 focus:ring-green-600/20" />
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
                    <div class="flex items-center gap-2"><span class="w-12 font-semibold tabular-nums">{{ fmt(r.people) }}</span><div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-green-600" :style="{ width: Math.min(100, (r.people / maxPeople) * 100) + '%' }" /></div></div>
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
