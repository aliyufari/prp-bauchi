<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ArrowLeft, UserRound, ShieldCheck, Clock3, Monitor, MapPin } from 'lucide-vue-next'

const props = defineProps<{ person: Record<string, any>; kind: string }>()
const p = props.person
const fullName = [p.first_name, p.middle_name, p.last_name].filter(Boolean).join(' ')
const location = [p.state, p.zone, p.lga, p.ward, p.pu, p.constituency].filter(Boolean).join(' · ') || 'Not assigned'
const date = (v: string | null) => v ? new Date(v).toLocaleString() : 'No login recorded'
</script>

<template>
  <Head :title="`${kind} details`" />
  <AppLayout>
  <section class="space-y-5">
    <Link :href="route(`${kind.toLowerCase().replaceAll(' ', '-') }s.index`)" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-red-700"><ArrowLeft class="h-4 w-4" /> Back</Link>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
      <div class="flex flex-wrap items-center gap-4">
        <img v-if="p.avatar_url || p.image_url" :src="p.avatar_url || p.image_url" :alt="fullName" class="h-20 w-20 rounded-full object-cover" />
        <div v-else class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-100"><UserRound class="h-9 w-9 text-gray-400" /></div>
        <div class="min-w-0 flex-1"><p class="text-xs font-bold uppercase tracking-wider text-red-700">{{ kind }}</p><h1 class="text-2xl font-black text-gray-900">{{ fullName }}</h1><p class="break-all text-sm text-gray-500">{{ p.email }}</p></div>
        <span class="rounded-full px-3 py-1 text-xs font-bold" :class="p.status === 'active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">{{ p.status || 'Unknown' }}</span>
      </div>
      <dl class="mt-7 grid gap-5 border-t border-gray-100 pt-6 sm:grid-cols-2 lg:grid-cols-3">
        <div><dt class="text-xs font-bold uppercase text-gray-400">Phone</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ p.phone || '—' }}</dd></div>
        <div><dt class="text-xs font-bold uppercase text-gray-400">Application ID</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ p.agent_no || p.application_id || '—' }}</dd></div>
        <div><dt class="text-xs font-bold uppercase text-gray-400">Type</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ p.agent_type_label || p.coordinator_type_label || p.supervisor_type_label || p.stakeholder_type_label || p.office_label || p.type || '—' }}</dd></div>
        <div class="sm:col-span-2 lg:col-span-3"><dt class="flex items-center gap-1 text-xs font-bold uppercase text-gray-400"><MapPin class="h-3.5 w-3.5"/> Assignment</dt><dd class="mt-1 text-sm font-semibold text-gray-900">{{ location }}</dd></div>
      </dl>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
      <h2 class="flex items-center gap-2 font-black text-gray-900"><ShieldCheck class="h-4 w-4 text-red-600"/> Account activity</h2>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div><p class="text-xs text-gray-400">Last login</p><p class="mt-1 text-sm font-semibold">{{ date(p.last_login_at) }}</p></div>
        <div><p class="text-xs text-gray-400">IP address</p><p class="mt-1 text-sm font-semibold">{{ p.last_login_ip || '—' }}</p></div>
        <div><p class="flex items-center gap-1 text-xs text-gray-400"><Monitor class="h-3.5 w-3.5"/> Browser</p><p class="mt-1 text-sm font-semibold">{{ p.last_login_browser || '—' }}</p></div>
        <div><p class="flex items-center gap-1 text-xs text-gray-400"><Clock3 class="h-3.5 w-3.5"/> Device</p><p class="mt-1 text-sm font-semibold">{{ p.last_login_device || '—' }}</p></div>
      </div>
    </div>
  </section>
  </AppLayout>
</template>
