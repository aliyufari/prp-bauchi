<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { KeyRound, LogOut, MapPin, Pencil, Users2 } from 'lucide-vue-next'
import { computed } from 'vue'
import { useScopeLabel } from '@/composables/useScopeLabel'

defineProps<{
  me: {
    name: string; application_id: string | null; location: Record<string, string>
    email: string | null; phone: string | null; gender: string | null
    occupation: string | null; education: string | null; avatar_url: string | null
    last_login_at: string | null; last_login_ip: string | null; last_login_browser: string | null; last_login_device: string | null
  }
  team: { id: string; name: string } | null
}>()

const { roleLabel, initials } = useScopeLabel()
const page = usePage()
const readOnlyIdentity = computed(() => (page.props as any).auth?.roles?.some((r: string) => ['party_agent', 'super_party_agent', 'coordinator', 'supervisory_agent', 'returning_officer', 'stakeholder', 'campaign_team_member'].includes(r)) ?? false)
const LABEL: Record<string, string> = { state: 'State', zone: 'Zone', lga: 'LGA', ward: 'Ward', pu: 'Polling unit' }
const cap = (v: string | null) => (v ? v.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) : null)
</script>

<template>
  <Head title="My profile" />

  <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm">
    <div class="h-20 bg-gradient-to-r from-red-600 to-red-800"></div>
    <div class="px-5 pb-5">
      <div class="-mt-10 flex items-end gap-4">
        <img v-if="me.avatar_url" :src="me.avatar_url" :alt="me.name" class="h-20 w-20 rounded-full border-4 border-white object-cover shadow" />
        <div v-else class="flex h-20 w-20 items-center justify-center rounded-full border-4 border-white bg-neutral-900 text-xl font-black text-white shadow">{{ initials }}</div>
        <div class="min-w-0 pb-1">
          <h1 class="truncate text-lg font-black text-neutral-900">{{ me.name }}</h1>
          <p class="text-xs font-bold uppercase tracking-wider text-red-600">{{ roleLabel }}</p>
        </div>
      </div>

      <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
        <div v-if="me.application_id"><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Member ID</dt><dd class="font-medium text-neutral-900">{{ me.application_id }}</dd></div>
        <div v-if="me.email"><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Email</dt><dd class="break-all font-medium text-neutral-900">{{ me.email }}</dd></div>
        <div v-if="me.phone"><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Phone</dt><dd class="font-medium text-neutral-900">{{ me.phone }}</dd></div>
        <div v-if="me.gender"><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Gender</dt><dd class="font-medium text-neutral-900">{{ cap(me.gender) }}</dd></div>
        <div v-if="me.occupation"><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Occupation</dt><dd class="font-medium text-neutral-900">{{ cap(me.occupation) }}</dd></div>
        <div v-if="me.education"><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Education</dt><dd class="font-medium text-neutral-900">{{ cap(me.education) }}</dd></div>
      </dl>
    </div>
  </section>

  <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h2 class="flex items-center gap-2 text-sm font-black text-neutral-900"><MapPin class="h-4 w-4 text-red-600" />My location</h2>
    <dl v-if="Object.keys(me.location).length" class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
      <div v-for="(name, level) in me.location" :key="level">
        <dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">{{ LABEL[level] ?? level }}</dt>
        <dd class="font-medium text-neutral-900">{{ name }}</dd>
      </div>
    </dl>
    <p v-else class="mt-2 text-sm text-neutral-500">No location has been assigned to your account yet.</p>
  </section>

  <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h2 class="flex items-center gap-2 text-sm font-black text-neutral-900"><Users2 class="h-4 w-4 text-red-600" />Recent account access</h2>
    <dl class="mt-3 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
      <div><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Last login</dt><dd class="font-medium text-neutral-900">{{ me.last_login_at ? new Date(me.last_login_at).toLocaleString() : 'No login recorded' }}</dd></div>
      <div><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">IP address</dt><dd class="font-medium text-neutral-900">{{ me.last_login_ip || '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Browser</dt><dd class="font-medium text-neutral-900">{{ me.last_login_browser || '—' }}</dd></div>
      <div><dt class="text-[11px] font-bold uppercase tracking-wide text-neutral-400">Device</dt><dd class="font-medium text-neutral-900">{{ me.last_login_device || '—' }}</dd></div>
    </dl>
  </section>

  <section v-if="team" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h2 class="flex items-center gap-2 text-sm font-black text-neutral-900"><Users2 class="h-4 w-4 text-red-600" />My team</h2>
    <p class="mt-2 text-sm font-medium text-neutral-800">{{ team.name }}</p>
  </section>

  <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
    <Link v-if="!readOnlyIdentity" :href="route('profile.edit')" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-bold text-neutral-900 transition hover:border-red-600 hover:text-red-700">
      <Pencil class="h-4 w-4 text-red-600" />Edit my details
    </Link>
    <Link :href="route('password.edit')" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 text-sm font-bold text-neutral-900 transition hover:border-red-600 hover:text-red-700">
      <KeyRound class="h-4 w-4 text-red-600" />Change password
    </Link>
    <button type="button" @click="router.post(route('logout'))" class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-bold text-red-700 transition hover:border-red-600 hover:bg-red-100">
      <LogOut class="h-4 w-4" />Log out
    </button>
  </div>
</template>
