<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Phone, Search } from 'lucide-vue-next'
import OfficeSelect from '@/components/pu/OfficeSelect.vue'
import TaskStatus from '@/components/pu/TaskStatus.vue'

interface Agent {
  id: string; name: string; phone: string | null; avatar_url: string | null
  pu: { id: string; name: string; code: string } | null
  officials_done: boolean; sheet_done: boolean; video_done: boolean; figures_done: boolean
}
const props = defineProps<{ agents: Agent[]; office: string; offices: { value: string; label: string }[]; search?: string | null }>()

const office = ref(props.office)
const tab = ref<'all' | 'help' | 'done'>('all')
const finished = (a: Agent) => a.officials_done && a.sheet_done && a.video_done && a.figures_done
const shown = computed(() => props.agents.filter((a) => tab.value === 'all' || (tab.value === 'done' ? finished(a) : !finished(a))))
const helpCount = computed(() => props.agents.filter((a) => !finished(a)).length)
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
    <p class="mt-1 text-sm text-neutral-600">Agents under you and how far each has got.</p>
    <div class="mt-4 space-y-3">
      <OfficeSelect v-if="offices.length > 1" v-model="office" :offices="offices" />
      <label class="block">
        <span class="relative block">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
          <input v-model="search" type="search" placeholder="Agent name"
            class="h-10 w-full rounded-lg border border-[#e5e7eb] bg-white pl-9 pr-3 text-sm text-[#111827] outline-none [-webkit-text-fill-color:#111827] placeholder:text-[#9ca3af] focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/30" />
        </span>
      </label>
      <div class="flex flex-wrap gap-2">
        <button v-for="t in [{ v: 'all', l: 'All', c: agents.length }, { v: 'help', l: 'Need help', c: helpCount }, { v: 'done', l: 'Finished', c: agents.length - helpCount }]" :key="t.v" type="button"
          class="rounded-full border px-3.5 py-2 text-sm font-bold transition"
          :class="tab === t.v ? 'border-red-600 bg-red-600 text-white' : 'border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50'"
          @click="tab = t.v as typeof tab">{{ t.l }} <span class="opacity-70">{{ t.c }}</span></button>
      </div>
    </div>
  </section>

  <p v-if="!shown.length" class="rounded-2xl border border-dashed border-neutral-300 bg-white p-8 text-center text-sm text-neutral-500">No agents found in your ward.</p>

  <ul class="space-y-3">
    <li v-for="a in shown" :key="a.id" class="rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm">
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
          <TaskStatus :done="a.officials_done" done-text="Names" pending-text="Names" />
          <TaskStatus :done="a.sheet_done" done-text="Sheet" pending-text="Sheet" />
          <TaskStatus :done="a.video_done" done-text="Video" pending-text="Video" />
          <TaskStatus :done="a.figures_done" done-text="Figures" pending-text="Figures" />
        </div>
        <div class="flex gap-2 text-xs font-bold">
          <Link :href="route('portal.pus.show', { pu: a.pu.id, office })" class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700">Open unit</Link>
        </div>
      </div>
    </li>
  </ul>
</template>
