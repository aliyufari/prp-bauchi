<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, watch } from 'vue'
import { Search, Monitor, Clock3, Globe2 } from 'lucide-vue-next'
const props = defineProps<{ events: { data: any[]; links: any[]; current_page: number; last_page: number; total: number }; filters: { search?: string } }>()
const search = ref(props.filters.search ?? '')
let debounce: ReturnType<typeof setTimeout> | null = null
watch(search, value => { if (debounce) clearTimeout(debounce); debounce = setTimeout(() => router.get(route('login-activity.index'), { search: value || undefined }, { preserveState: true, replace: true }), 300) })
const when = (v: string) => new Date(v).toLocaleString()
</script>
<template>
  <Head title="Login activity"/>
  <AppLayout>
  <section class="space-y-5">
    <header><h1 class="text-2xl font-black text-gray-900">Login activity</h1><p class="mt-1 text-sm text-gray-500">A record of account sign-ins, including IP address, browser and device details.</p></header>
    <div class="relative max-w-md"><Search class="absolute left-3 top-3 h-4 w-4 text-gray-400"/><input v-model="search" placeholder="Search name, email or IP address" class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-red-400"/></div>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
      <div v-if="!events.data.length" class="p-10 text-center text-sm text-gray-500">No login activity found.</div>
      <div v-for="event in events.data" :key="event.id" class="grid gap-2 border-b border-gray-100 p-4 last:border-0 sm:grid-cols-[1.3fr_1fr_1fr_1fr] sm:items-center">
        <div><p class="font-bold text-gray-900">{{ event.user ? [event.user.first_name,event.user.last_name].filter(Boolean).join(' ') : (event.email || 'Deleted user') }}</p><p class="break-all text-xs text-gray-500">{{ event.email }}</p></div>
        <div class="flex items-center gap-2 text-sm text-gray-700"><Globe2 class="h-4 w-4 text-gray-400"/>{{ event.ip_address || 'Unknown IP' }}</div>
        <div class="flex items-center gap-2 text-sm text-gray-700"><Monitor class="h-4 w-4 text-gray-400"/>{{ event.browser || 'Unknown' }} · {{ event.platform || 'Unknown' }} · {{ event.device || 'Unknown' }}</div>
        <div class="flex items-center gap-2 text-xs text-gray-500"><Clock3 class="h-4 w-4"/>{{ when(event.logged_in_at) }}</div>
      </div>
    </div>
    <div class="flex items-center justify-between text-sm text-gray-500"><span>{{ events.total }} login events</span><div class="flex gap-2"><button v-for="link in events.links" :key="link.label" :disabled="!link.url" @click="link.url && router.visit(link.url)" class="rounded-lg border px-3 py-1.5 disabled:opacity-40" :class="link.active ? 'border-red-600 bg-red-50 text-red-700' : 'border-gray-200'" v-html="link.label"/></div></div>
  </section>
  </AppLayout>
</template>
