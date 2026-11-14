<script setup lang="ts">
/** Executive banner: the candidate, the campaign's headline numbers and the readiness ring. */
import aspirantFallback from '@/assets/images/governor-aspirant.jpeg'
import Button from '@/components/ui/Button.vue'
import { Download, MapPin, Quote } from 'lucide-vue-next'
import { computed } from 'vue'
import ReadinessRing from './ReadinessRing.vue'

const props = defineProps<{
  aspirant: { name: string; title: string; state: string | null; vision: string | null; imageUrl: string | null; overallProgress: number | null; totalSupporters: number | null; supportersGrowthThisWeek: number | null; campaignTeamMembers: number } | null
  readiness: { score: number; parts: { label: string; value: number }[] }
  coverage: { lgasCovered: number; totalLgas: number }
  members: number
  exportHref: string
  updated: string
}>()

const fmt = (n: number | null | undefined) => (Number.isFinite(Number(n)) ? Number(n) : 0).toLocaleString()
const campaignProgress = computed(() => Math.min(100, Math.max(0, Math.round(props.aspirant?.overallProgress ?? 0))))
const supporters = computed(() => props.aspirant?.totalSupporters ?? props.members)
</script>

<template>
  <section class="relative isolate overflow-hidden rounded-3xl text-white shadow-xl" style="background: linear-gradient(135deg, #0d3b26 0%, #092b1c 45%, #061a11 100%)">
    <!-- decorative glow + grid -->
    <div class="pointer-events-none absolute -top-24 -right-24 h-80 w-80 rounded-full opacity-30 blur-3xl" style="background: radial-gradient(circle, #e8c44a, transparent 70%)" />
    <div class="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full opacity-20 blur-3xl" style="background: radial-gradient(circle, #22c55e, transparent 70%)" />
    <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 36px 36px" />

    <div class="relative grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:gap-10 lg:p-9 2xl:p-12">
      <div class="flex min-w-0 flex-col gap-6 sm:flex-row sm:items-center sm:gap-8">
        <div class="relative mx-auto w-40 shrink-0 sm:mx-0 sm:w-44 lg:w-52 2xl:w-60">
          <div class="absolute -inset-2 rounded-[1.75rem] opacity-60 blur-xl" style="background: linear-gradient(135deg, #e8c44a, transparent)" />
          <img :src="aspirant?.imageUrl || aspirantFallback" :alt="aspirant?.name || 'Governorship aspirant'" class="relative aspect-[4/5] w-full rounded-3xl border border-[#e8c44a]/40 object-cover object-top shadow-2xl" />
        </div>

        <div class="min-w-0 text-center sm:text-left">
          <span class="inline-flex items-center gap-1.5 rounded-full border border-[#e8c44a]/30 bg-[#e8c44a]/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#e8c44a]">
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#e8c44a]" />{{ aspirant?.title || 'Governorship Candidate' }}
          </span>
          <h2 class="mt-3 text-2xl leading-tight font-black tracking-tight sm:text-3xl 2xl:text-4xl">{{ aspirant?.name || 'Campaign overview' }}</h2>
          <p class="mt-1 flex items-center justify-center gap-1.5 text-xs font-medium text-white/60 sm:justify-start sm:text-sm"><MapPin class="h-3.5 w-3.5" />{{ aspirant?.state || 'Bauchi State' }} · Updated {{ updated }}</p>
          <p v-if="aspirant?.vision" class="mt-4 flex max-w-2xl gap-2 text-sm leading-relaxed text-white/75 sm:text-base"><Quote class="mt-1 h-4 w-4 shrink-0 text-[#e8c44a]" /><span class="line-clamp-3">{{ aspirant.vision }}</span></p>

          <div class="mt-5">
            <div class="mb-1.5 flex items-center justify-between text-xs font-semibold text-white/70"><span>Campaign progress</span><span class="text-[#e8c44a]">{{ campaignProgress }}%</span></div>
            <div class="h-2.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full transition-all duration-700" :style="{ width: campaignProgress + '%', background: 'linear-gradient(90deg,#4ade80,#e8c44a)' }" /></div>
          </div>

          <dl class="mt-5 grid grid-cols-3 gap-2 sm:gap-3">
            <div class="rounded-2xl bg-white/[0.07] p-3 ring-1 ring-white/10 backdrop-blur"><dt class="text-[10px] font-bold tracking-wider text-white/50 uppercase">Supporters</dt><dd class="mt-1 text-lg font-black sm:text-2xl">{{ fmt(supporters) }}</dd><p v-if="aspirant?.supportersGrowthThisWeek" class="text-[10px] font-semibold text-green-300">+{{ fmt(aspirant.supportersGrowthThisWeek) }} this week</p></div>
            <div class="rounded-2xl bg-white/[0.07] p-3 ring-1 ring-white/10 backdrop-blur"><dt class="text-[10px] font-bold tracking-wider text-white/50 uppercase">LGAs reached</dt><dd class="mt-1 text-lg font-black sm:text-2xl">{{ coverage.lgasCovered }}<span class="text-sm font-semibold text-white/40">/{{ coverage.totalLgas }}</span></dd></div>
            <div class="rounded-2xl bg-white/[0.07] p-3 ring-1 ring-white/10 backdrop-blur"><dt class="text-[10px] font-bold tracking-wider text-white/50 uppercase">Campaign team</dt><dd class="mt-1 text-lg font-black sm:text-2xl">{{ fmt(aspirant?.campaignTeamMembers) }}</dd></div>
          </dl>
        </div>
      </div>

      <div class="flex flex-col items-center justify-between gap-5 rounded-2xl bg-white/[0.06] p-5 ring-1 ring-white/10 backdrop-blur">
        <ReadinessRing :score="readiness.score" :parts="readiness.parts" />
        <Button :href="exportHref" external class="w-full !bg-[#e8c44a] !text-[#0d3b26] hover:!bg-[#f0d060]"><Download class="h-4 w-4 shrink-0" />Download full report</Button>
      </div>
    </div>
  </section>
</template>
