<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { MapPin, Users2 } from 'lucide-vue-next'

defineProps<{
  profile: { name: string; application_id: string | null; location: Record<string, string> }
  team: { id: string; name: string } | null
  ledTeams: { id: string; name: string }[]
}>()

const LABEL: Record<string, string> = { state: 'State', zone: 'Zone', lga: 'LGA', ward: 'Ward', pu: 'Polling unit' }
</script>

<template>
  <Head title="Home" />

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

  <section v-if="team || ledTeams.length" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <h3 class="flex items-center gap-2 text-sm font-bold text-gray-900"><Users2 class="h-4 w-4 text-green-700" />Your team</h3>
    <ul class="mt-3 space-y-2 text-sm">
      <li v-if="team" class="text-gray-700">{{ team.name }}</li>
      <li v-for="t in ledTeams" :key="t.id">
        <Link :href="route('campaign-teams.show', t.id)" class="font-medium text-green-700 hover:underline">{{ t.name }} (you lead)</Link>
      </li>
    </ul>
  </section>
</template>