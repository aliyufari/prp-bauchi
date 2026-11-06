<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import SearchSelect from '@/components/ui/form/SearchSelect.vue'
import type { SelectOption } from '@/components/ui/form/types'
import StatCard from '@/components/dashboard/StatCard.vue'
import { Download } from 'lucide-vue-next'
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

const currentDateTime = computed(() =>
  new Date().toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit',
  }),
)
</script>

<template>
  <div class="space-y-6 sm:space-y-8">
    <!-- Header -->
    <section class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
      <div>
        <h2 class="text-lg sm:text-xl font-black text-gray-900">Welcome back, {{ auth.user?.first_name }}</h2>
        <p class="text-xs sm:text-sm text-gray-500 font-medium mt-1">{{ currentDateTime }}</p>
      </div>

      <div class="grid w-full grid-cols-1 gap-2 sm:flex sm:w-auto sm:items-center">
        <SearchSelect
          v-model="selectedLga"
          :options="lgaOptions"
          placeholder="All LGAs"
          search-placeholder="Search LGAs…"
          class="w-full sm:min-w-44"
        />
        <SearchSelect
          v-model="selectedWard"
          :options="wardOptions"
          placeholder="All wards"
          search-placeholder="Search wards…"
          class="w-full sm:min-w-44"
        />
        <a
          :href="route('dashboard.export', { lga: selectedLga || undefined, ward: selectedWard || undefined })"
          class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-green-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600 focus-visible:ring-offset-2"
        >
          <Download class="h-4 w-4" />
          Download report
        </a>
      </div>
    </section>

    <!-- Stats -->
    <section class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
      <StatCard :value="stats.totalMembers" label="Total Members" icon="Users" :growth="stats.totalMembersGrowth" icon-bg="bg-green-100" icon-color="text-green-700" />
      <StatCard :value="stats.partyAgents" label="Party Agents" icon="UserCheck" :growth="stats.partyAgentsGrowth" icon-bg="bg-blue-100" icon-color="text-blue-700" />
      <StatCard :value="stats.coordinators" label="Coordinators" icon="Briefcase" :growth="stats.coordinatorsGrowth" icon-bg="bg-purple-100" icon-color="text-purple-700" />
      <StatCard :value="stats.stakeholders" label="Stakeholders" icon="Handshake" :growth="stats.stakeholdersGrowth" icon-bg="bg-amber-100" icon-color="text-amber-700" />
      <StatCard :value="stats.upcomingActivities" label="Upcoming Activities" icon="Calendar" subtitle="This Week" icon-bg="bg-red-100" icon-color="text-red-600" />
      <StatCard :value="stats.lgasCovered" label="LGAs Covered" icon="BarChart3" :subtitle="`Out of ${stats.totalLgas} LGAs`" icon-bg="bg-teal-100" icon-color="text-teal-700" />
    </section>

    <!-- Main analytics -->
    <section class="grid grid-cols-1 items-stretch gap-4 sm:gap-5 md:grid-cols-2 xl:grid-cols-12">
      <div class="min-w-0 xl:col-span-4"><MembershipChart :data="membershipByLga" /></div>
      <div class="min-w-0 xl:col-span-3"><CampaignActivities :activities="campaignActivities" :total-activities="campaignActivities.length" /></div>
      <div class="min-w-0 md:col-span-2 xl:col-span-5"><GovernorshipAspirant :aspirant="aspirant" /></div>
    </section>

    <!-- Support insights -->
    <section class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-12">
      <div class="min-w-0 xl:col-span-3"><ElectionStrategy :activities="electionStrategyActivities" /></div>
      <div class="min-w-0 xl:col-span-3"><UpcomingActivities :activities="upcomingActivities" /></div>
      <div class="min-w-0 xl:col-span-6"><LgaSupportMap :data="lgaSupportData" /></div>
    </section>

    <!-- Management -->
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 xl:grid-cols-4">
      <AgentDeployment :deployment="agentDeployment" />
      <FinancialSummary :summary="financialSummary" />
      <Announcements :announcements="announcements" />
      <RecentSupporters :supporters="recentSupporters" />
    </section>
  </div>
</template>