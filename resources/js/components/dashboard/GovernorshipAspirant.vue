<script setup lang="ts">
import { Eye, Target, Users, UserRound, ArrowUpRight } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import aspirantImage from '@/assets/images/governor-aspirant.jpeg'

interface Aspirant {
  name?: string | null
  title?: string | null
  state?: string | null
  image?: string | null
  imageUrl?: string | null
  vision?: string | null
  mission?: string | null
  campaignProgress?: number | null
  overallProgress?: number | null
  totalSupporters?: number | null
  supportersGrowth?: number | null
  supportersGrowthThisWeek?: number | null
  campaignTeamMembers?: number | null
}

const props = defineProps<{ aspirant: Aspirant | null }>()
const format = (value?: number | null) => (value ?? 0).toLocaleString()
const percent = (value?: number | null) => Math.min(Math.max(value ?? 0, 0), 100)
</script>

<template>
  <section class="flex h-full min-h-[350px] flex-col overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
      <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">Leadership profile</p>
        <h3 class="mt-1 text-base font-bold text-gray-900">Governorship aspirant</h3>
      </div>
      <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Bauchi State</span>
    </div>

    <div v-if="aspirant" class="grid flex-1 grid-cols-1 gap-5 p-5 sm:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
      <div class="flex min-w-0 flex-col items-center justify-center rounded-xl bg-gradient-to-br from-emerald-50 to-gray-50 p-5 text-center">
        <img :src="aspirant.imageUrl || aspirant.image || aspirantImage" :alt="aspirant.name || 'Governorship aspirant'" class="h-28 w-28 rounded-2xl border-4 border-white object-cover shadow-md sm:h-32 sm:w-32" />
        <h4 class="mt-4 text-lg font-extrabold leading-tight text-gray-900">{{ aspirant.name || 'Aspirant profile' }}</h4>
        <p class="mt-1 text-sm font-medium text-gray-500">{{ aspirant.title || 'Governorship Candidate' }}</p>
        <p class="mt-1 text-xs text-gray-400">{{ aspirant.state || 'Bauchi State' }}</p>
        <Link :href="route('aspirants.index')" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800">
          View profile <ArrowUpRight class="h-3.5 w-3.5" />
        </Link>
      </div>

      <div class="flex min-w-0 flex-col justify-between gap-5">
        <div class="space-y-4">
          <div class="flex items-start gap-3">
            <span class="rounded-lg bg-emerald-50 p-2 text-emerald-700"><Eye class="h-4 w-4" /></span>
            <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wide text-gray-700">Vision</p><p class="mt-1 text-sm leading-relaxed text-gray-600">{{ aspirant.vision || 'Vision statement has not been added yet.' }}</p></div>
          </div>
          <div class="flex items-start gap-3">
            <span class="rounded-lg bg-emerald-50 p-2 text-emerald-700"><Target class="h-4 w-4" /></span>
            <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wide text-gray-700">Mission</p><p class="mt-1 text-sm leading-relaxed text-gray-600">{{ aspirant.mission || 'Mission statement has not been added yet.' }}</p></div>
          </div>
        </div>

        <div class="space-y-3">
          <div>
            <div class="mb-1.5 flex items-center justify-between"><p class="text-xs font-semibold text-gray-500">Campaign progress</p><p class="text-sm font-bold text-gray-900">{{ percent(aspirant.overallProgress ?? aspirant.campaignProgress) }}%</p></div>
            <div class="h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-emerald-600 transition-all" :style="{ width: `${percent(aspirant.overallProgress ?? aspirant.campaignProgress)}%` }" /></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-3"><div class="flex items-center gap-2 text-gray-500"><Users class="h-4 w-4" /><span class="text-xs font-medium">Supporters</span></div><p class="mt-2 text-xl font-extrabold text-gray-900">{{ format(aspirant.totalSupporters) }}</p><p class="mt-0.5 text-[11px] font-medium text-emerald-700">+{{ format(aspirant.supportersGrowthThisWeek ?? aspirant.supportersGrowth) }} this week</p></div>
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-3"><div class="flex items-center gap-2 text-gray-500"><UserRound class="h-4 w-4" /><span class="text-xs font-medium">Campaign team</span></div><p class="mt-2 text-xl font-extrabold text-gray-900">{{ format(aspirant.campaignTeamMembers) }}</p><p class="mt-0.5 text-[11px] text-gray-500">Team members</p></div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
      <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><UserRound class="h-7 w-7" /></div>
      <h4 class="mt-4 text-base font-bold text-gray-900">No governorship aspirant added</h4>
      <p class="mt-1 max-w-sm text-sm leading-relaxed text-gray-500">The aspirant profile, vision, mission and campaign figures will appear here once a candidate is registered.</p>
      <Link :href="route('aspirants.index')" class="mt-5 inline-flex items-center gap-2 rounded-lg border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50">Manage aspirants <ArrowUpRight class="h-4 w-4" /></Link>
    </div>
  </section>
</template>
