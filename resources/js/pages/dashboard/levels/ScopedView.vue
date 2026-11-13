<script setup lang="ts">
import { computed } from 'vue'
import { AlertTriangle, Download, MapPin } from 'lucide-vue-next'
import StatCard from '@/components/dashboard/StatCard.vue'
import { usePermissions } from '@/composables/usePermissions'
import { useScopeLabel } from '@/composables/useScopeLabel'

interface Props {
  scope: { level: string | null; name: string | null }
  notice: string | null
  stats: {
    members: number; party_agents: number; coordinators: number
    polling_units: number; registered_voters: number
    results: { total: number; verified: number; pending: number; disputed: number }
  }
  breakdown: { level: string | null; rows: { id: string; name: string; people: number; results: number }[] }
}
const props = defineProps<Props>()

const { auth } = usePermissions()
const { roleLabel } = useScopeLabel()

const LEVEL_PLURAL: Record<string, string> = { zone: 'Zones', lga: 'LGAs', ward: 'Wards', pu: 'Polling units', state: 'States' }
const fmt = (n: number) => n.toLocaleString()

const maxPeople = computed(() => Math.max(1, ...props.breakdown.rows.map((r) => r.people)))
const pct = (n: number) => (props.stats.results.total ? Math.round((n / props.stats.results.total) * 100) : 0)
</script>

<template>
  <div class="space-y-6 sm:space-y-8">
    <section class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-lg sm:text-xl font-black text-gray-900">Welcome back, {{ auth.user?.first_name }}</h2>
        <p class="mt-1 flex items-center gap-1.5 text-xs sm:text-sm font-medium text-gray-500">
          <MapPin class="h-3.5 w-3.5" />
          {{ roleLabel }}<span v-if="scope.name"> · {{ scope.name }}</span>
        </p>
      </div>
      <a :href="route('dashboard.export')" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-green-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-600 focus-visible:ring-offset-2">
        <Download class="h-4 w-4" />
        Download report
      </a>
    </section>

    <div v-if="notice" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
      <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
      {{ notice }}
    </div>

    <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-5">
      <StatCard :value="stats.party_agents" label="Party Agents" icon="UserCheck" icon-bg="bg-green-50" icon-color="text-green-900" />
      <StatCard :value="stats.coordinators" label="Coordinators" icon="Briefcase" icon-bg="bg-gray-100" icon-color="text-gray-700" />
      <StatCard :value="stats.polling_units" label="Polling Units" icon="MapPin" icon-bg="bg-amber-100" icon-color="text-amber-700" />
      <StatCard :value="stats.registered_voters" label="Registered Voters" icon="Users" icon-bg="bg-teal-100" icon-color="text-green-700" />
      <StatCard :value="stats.members" label="Members" icon="Users" icon-bg="bg-green-100" icon-color="text-green-700" />
    </section>

    <section class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-12">
      <!-- Results status -->
      <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm lg:col-span-4">
        <h3 class="text-sm font-bold text-gray-900">Results</h3>
        <p class="mt-1 text-xs text-gray-400">{{ fmt(stats.results.total) }} uploaded</p>

        <div class="mt-4 flex h-2.5 overflow-hidden rounded-full bg-gray-100">
          <div class="bg-green-500" :style="{ width: pct(stats.results.verified) + '%' }" />
          <div class="bg-amber-400" :style="{ width: pct(stats.results.pending) + '%' }" />
          <div class="bg-red-500" :style="{ width: pct(stats.results.disputed) + '%' }" />
        </div>

        <dl class="mt-4 space-y-2 text-sm">
          <div class="flex justify-between"><dt class="flex items-center gap-2 text-gray-600"><span class="h-2 w-2 rounded-full bg-green-500" />Verified</dt><dd class="font-semibold text-gray-900">{{ fmt(stats.results.verified) }}</dd></div>
          <div class="flex justify-between"><dt class="flex items-center gap-2 text-gray-600"><span class="h-2 w-2 rounded-full bg-amber-400" />Pending</dt><dd class="font-semibold text-gray-900">{{ fmt(stats.results.pending) }}</dd></div>
          <div class="flex justify-between"><dt class="flex items-center gap-2 text-gray-600"><span class="h-2 w-2 rounded-full bg-red-500" />Disputed</dt><dd class="font-semibold text-gray-900">{{ fmt(stats.results.disputed) }}</dd></div>
        </dl>
      </div>

      <!-- Breakdown by child level -->
      <div class="rounded-2xl border border-gray-100 bg-white shadow-sm lg:col-span-8">
        <div class="border-b border-gray-50 p-5">
          <h3 class="text-sm font-bold text-gray-900">
            {{ LEVEL_PLURAL[breakdown.level ?? ''] ?? 'Areas' }}<span v-if="scope.name"> in {{ scope.name }}</span>
          </h3>
        </div>

        <p v-if="!breakdown.rows.length" class="p-8 text-center text-sm text-gray-400">Nothing to show for this area yet.</p>

        <table v-else class="w-full text-left">
          <thead>
            <tr class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
              <th class="px-5 py-3">Name</th>
              <th class="px-5 py-3">People</th>
              <th class="px-5 py-3 text-right">Results</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in breakdown.rows" :key="row.id" class="border-t border-gray-50">
              <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ row.name }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                  <div class="h-1.5 w-28 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-green-500" :style="{ width: (row.people / maxPeople) * 100 + '%' }" />
                  </div>
                  <span class="text-sm text-gray-700">{{ fmt(row.people) }}</span>
                </div>
              </td>
              <td class="px-5 py-3 text-right text-sm text-gray-700">{{ fmt(row.results) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>