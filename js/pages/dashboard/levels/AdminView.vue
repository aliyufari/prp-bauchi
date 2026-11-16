<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import SearchSelect from '@/components/ui/form/SearchSelect.vue'
import type { SelectOption } from '@/components/ui/form/types'
import StatCard from '@/components/dashboard/StatCard.vue'
import { Download } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import MembershipChart from '@/components/dashboard/MembershipChart.vue'
import CampaignActivities from '@/components/dashboard/CampaignActivities.vue'
import GovernorshipAspirant from '@/components/dashboard/GovernorshipAspirant.vue'
import ElectionStrategy from '@/components/dashboard/ElectionStrategy.vue'
import UpcomingActivities from '@/components/dashboard/UpcomingActivities.vue'
import LgaSupportMap from '@/components/dashboard/LgaSupportMap.vue'
import AgentDeployment from '@/components/dashboard/AgentDeployment.vue'
import FinancialSummary from '@/components/dashboard/FinancialSummary.vue'
import Announcements from '@/components/dashboard/Announcements.vue'
import RecentSupporters from '@/components/dashboard/RecentSupporters.vue'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps<{
  stats: {
    totalMembers: number; totalMembersGrowth: number
    partyAgents: number; partyAgentsGrowth: number
    coordinators: number; coordinatorsGrowth: number
    stakeholders: number; stakeholdersGrowth: number
    upcomingActivities: number; lgasCovered: number; totalLgas: number
  }
  membershipByLga: { lga: string; count: number }[]
  campaignActivities: { name: string; percentage: number; color: string }[]
  upcomingActivities: any[]
  electionStrategyActivities: any[]
  lgaSupportData: { lga: string; supportLevel: 'High' | 'Medium' | 'Low' }[]
  aspirant: any
  agentDeployment: any
  financialSummary: any
  announcements: any[]
  recentSupporters: any[]
  lgas: { id: string; name: string }[]
  wards: { id: string; name: string; lga_id: string }[]
  selectedLga: string
  selectedWard: string
  /** Aspirant view: same dashboard, but only widgets that have real data are shown. */
  readOnly?: boolean
}>()

const { auth } = usePermissions()

const selectedLga = ref<string | null>(props.selectedLga || null)
const selectedWard = ref<string | null>(props.selectedWard || null)

const lgaOptions = computed<SelectOption[]>(() => props.lgas.map((lga) => ({ value: lga.id, label: lga.name })))
const wardOptions = computed<SelectOption[]>(() => props.wards.filter((ward) => !selectedLga.value || ward.lga_id === selectedLga.value).map((ward) => ({ value: ward.id, label: ward.name })))

watch(selectedLga, (nextLga) => {
  if (selectedWard.value && !props.wards.some((ward) => ward.id === selectedWard.value && (!nextLga || ward.lga_id === nextLga))) {
    selectedWard.value = ''
  }
}, { flush: 'sync' })

watch([selectedLga, selectedWard], ([lga, ward]) => {
  router.get(route('dashboard'), { lga: lga || undefined, ward: ward || undefined }, {
    preserveState: true, preserveScroll: true, replace: true,
  })
})

/* Read-only (aspirant) view hides every widget that has no data source yet. */
const has = (list: unknown) => Array.isArray(list) && list.length > 0
const show = computed(() => ({
  activities: !props.readOnly || has(props.campaignActivities),
  strategy: !props.readOnly || has(props.electionStrategyActivities),
  upcoming: !props.readOnly || has(props.upcomingActivities),
  support: !props.readOnly || has(props.lgaSupportData),
  finance: !props.readOnly || Boolean(props.financialSummary && Object.values(props.financialSummary).some(Boolean)),
  announcements: !props.readOnly || has(props.announcements),
  supporters: !props.readOnly || has(props.recentSupporters),
}))
const showStrategySection = computed(() => show.value.strategy || show.value.upcoming || show.value.support)
const strategyCols = computed(() => [show.value.strategy, show.value.upcoming, show.value.support].filter(Boolean).length)
const operationsCols = computed(() => [true, show.value.finance, show.value.announcements, show.value.supporters].filter(Boolean).length)

// Shared layout classes so every section is built the same way.
const headingClass = 'text-[11px] font-bold uppercase tracking-wider text-gray-400'
const gridClass = 'grid grid-cols-1 items-stretch gap-4 sm:gap-5 md:grid-cols-2'
// `[&>*]:h-full` makes each card fill its cell so a row of cards ends on one line.
const cellClass = 'min-w-0 [&>*]:h-full'

const currentDateTime = computed(() =>
  new Date().toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit',
  }),
)
</script>

<template>
  <div class="space-y-8">
    <!-- Header: greeting + scope filters + report -->
    <section class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
      <div>
        <h2 class="text-lg font-black text-gray-900 sm:text-xl">{{ readOnly ? 'Campaign report' : 'Welcome back' }}, {{ auth.user?.first_name }}</h2>
        <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">{{ currentDateTime }}</p>
      </div>

      <div class="grid w-full grid-cols-1 gap-2 sm:flex sm:w-auto sm:flex-wrap sm:items-center xl:flex-nowrap">
        <SearchSelect
          v-model="selectedLga"
          :options="lgaOptions"
          placeholder="All LGAs"
          search-placeholder="Search LGAs…"
          class="w-full sm:w-48"
        />
        <SearchSelect
          v-model="selectedWard"
          :options="wardOptions"
          placeholder="All wards"
          search-placeholder="Search wards…"
          class="w-full sm:w-48"
        />
        <Button :href="route('dashboard.export', { lga: selectedLga || undefined, ward: selectedWard || undefined })" external class="w-full sm:w-auto">
          <Download class="h-4 w-4 shrink-0" />
          Download report
        </Button>
      </div>
    </section>

    <!-- 1. Overview: headline numbers -->
    <section aria-labelledby="overview-heading" class="space-y-3">
      <h3 id="overview-heading" :class="headingClass">Overview</h3>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 2xl:grid-cols-6">
        <StatCard :value="stats.totalMembers" label="Total Members" icon="Users" :growth="stats.totalMembersGrowth" icon-bg="bg-green-100" icon-color="text-green-700" />
        <StatCard :value="stats.partyAgents" label="Party Agents" icon="UserCheck" :growth="stats.partyAgentsGrowth" icon-bg="bg-amber-100" icon-color="text-amber-700" />
        <StatCard :value="stats.coordinators" label="Coordinators" icon="Briefcase" :growth="stats.coordinatorsGrowth" icon-bg="bg-gray-100" icon-color="text-gray-700" />
        <StatCard :value="stats.stakeholders" label="Stakeholders" icon="Handshake" :growth="stats.stakeholdersGrowth" icon-bg="bg-green-100" icon-color="text-green-700" />
        <StatCard v-if="show.upcoming" :value="stats.upcomingActivities" label="Upcoming Activities" icon="Calendar" subtitle="This Week" icon-bg="bg-amber-100" icon-color="text-amber-700" />
        <StatCard :value="stats.lgasCovered" label="LGAs Covered" icon="BarChart3" :subtitle="`Out of ${stats.totalLgas} LGAs`" icon-bg="bg-gray-100" icon-color="text-gray-700" />
      </div>
    </section>

    <!-- 2. Membership & campaign: aspirant card sits on the right and gets the most width -->
    <section aria-labelledby="reach-heading" class="space-y-3">
      <h3 id="reach-heading" :class="headingClass">Membership &amp; campaign</h3>
      <div :class="[gridClass, show.activities ? 'xl:grid-cols-[minmax(17rem,3.6fr)_minmax(15rem,2.8fr)_minmax(0,5.6fr)]' : 'xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]']">
        <div :class="cellClass"><MembershipChart :data="membershipByLga" /></div>
        <div v-if="show.activities" :class="cellClass">
          <CampaignActivities :activities="campaignActivities" :total-activities="campaignActivities.length" />
        </div>
        <div :class="[cellClass, show.activities ? 'md:col-span-2 xl:col-span-1' : '']"><GovernorshipAspirant :aspirant="aspirant" /></div>
      </div>
    </section>

    <!-- 3. Strategy & support: the plan, what is next, where support is strongest -->
    <section v-if="showStrategySection" aria-labelledby="strategy-heading" class="space-y-3">
      <h3 id="strategy-heading" :class="headingClass">Strategy &amp; support</h3>
      <div :class="[gridClass, strategyCols === 3 ? 'xl:grid-cols-[minmax(15rem,1fr)_minmax(15rem,1fr)_minmax(0,2fr)]' : strategyCols === 2 ? 'xl:grid-cols-2' : 'xl:grid-cols-1']">
        <div v-if="show.strategy" :class="cellClass"><ElectionStrategy :activities="electionStrategyActivities" /></div>
        <div v-if="show.upcoming" :class="cellClass"><UpcomingActivities :activities="upcomingActivities" /></div>
        <div v-if="show.support" :class="[cellClass, strategyCols === 3 ? 'md:col-span-2 xl:col-span-1' : '']"><LgaSupportMap :data="lgaSupportData" /></div>
      </div>
    </section>

    <!-- 4. Operations: people on the ground, money, communication -->
    <section aria-labelledby="operations-heading" class="space-y-3">
      <h3 id="operations-heading" :class="headingClass">Operations</h3>
      <div :class="['grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2 sm:gap-5', operationsCols >= 4 ? '2xl:grid-cols-4' : operationsCols === 3 ? 'xl:grid-cols-3' : '']">
        <div :class="cellClass"><AgentDeployment :deployment="agentDeployment" /></div>
        <div v-if="show.finance" :class="cellClass"><FinancialSummary :summary="financialSummary" /></div>
        <div v-if="show.announcements" :class="cellClass"><Announcements :announcements="announcements" /></div>
        <div v-if="show.supporters" :class="cellClass"><RecentSupporters :supporters="recentSupporters" /></div>
      </div>
    </section>
  </div>
</template>
