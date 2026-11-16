<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Phone, Search } from 'lucide-vue-next'
import OfficeSelect from '@/components/pu/OfficeSelect.vue'
import TaskStatus from '@/components/pu/TaskStatus.vue'

interface Agent {
  id: string; name: string; phone: string | null; avatar_url: string | null
  pu: { id: string; name: string; code: string } | null
  officials_done: boolean; result_done: boolean
}
const props = defineProps<{ agents: Agent[]; office: string; offices: { value: string; label: string }[]; search?: string | null }>()

const office = ref(props.office)
const search = ref(props.search ?? '')
let timer: ReturnType<typeof setTimeout>
const visit = () => router.get(route('portal.agents'), { office: office.value, search: search.value || undefined }, { preserveState: true, replace: true })
watch(office, visit)
watch(search, () => { clearTimeout(timer); timer = setTimeout(visit, 350) })
</script>

<template>
  <Head title="My agents" />

  <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h1 class="text-lg font-black text-neutral-900">Agents in my ward</h1>
    <p class="mt-1 text-sm text-neutral-600">The polling-unit agents under you and how far each has got. Open a unit to add what is missing.</p>
    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
      <OfficeSelect v-model="office" :offices="offices" />
      <label class="block">
        <span class="mb-1 block text-xs font-bold uppercase tracking-wider text-neutral-500">Search</span>
        <span class="relative block">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
          <input v-model="search" type="search" placeholder="Agent name"
            class="h-10 w-full rounded-lg border border-[#e5e7eb] bg-white pl-9 pr-3 text-sm text-[#111827] outline-none [-webkit-text-fill-color:#111827] placeholder:text-[#9ca3af] focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/30" />
        </span>
      </label>
    </div>
  </section>

  <p v-if="!agents.length" class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center text-sm text-neutral-500">No agents found in your ward.</p>

  <ul class="space-y-3">
    <li v-for="a in agents" :key="a.id" class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
      <div class="flex items-start gap-3">
        <img v-if="a.avatar_url" :src="a.avatar_url" :alt="a.name" class="h-10 w-10 shrink-0 rounded-full object-cover" />
        <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-sm font-black text-white">{{ a.name.slice(0, 1) }}</div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-bold text-neutral-900">{{ a.name }}</p>
          <p v-if="a.pu" class="truncate text-xs font-medium text-neutral-500"><span class="rounded bg-neutral-100 px-1.5 py-0.5">{{ a.pu.code }}</span> {{ a.pu.name }}</p>
          <p v-else class="text-xs font-medium text-amber-700">No polling unit assigned</p>
          <a v-if="a.phone" :href="`tel:${a.phone}`" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-red-700 hover:underline"><Phone class="h-3 w-3" />{{ a.phone }}</a>
        </div>
      </div>
      <div v-if="a.pu" class="mt-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap gap-1.5">
          <TaskStatus :done="a.officials_done" done-text="Officials added" pending-text="No officials" />
          <TaskStatus :done="a.result_done" done-text="Result uploaded" pending-text="No result" />
        </div>
        <div class="flex gap-2 text-xs font-bold">
          <Link :href="route('portal.pus.officials', a.pu.id)" class="rounded-lg border border-neutral-300 px-3 py-1.5 text-neutral-800 hover:border-red-600 hover:text-red-700">Officials</Link>
          <Link :href="route('portal.pus.result', { pu: a.pu.id, office })" class="rounded-lg bg-red-600 px-3 py-1.5 text-white hover:bg-red-700">Result</Link>
        </div>
      </div>
    </li>
  </ul>
</template>
