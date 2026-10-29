<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BrandMark from '@/components/auth/BrandMark.vue'
import { useAuthPanel } from '@/composables/useAuthPanel'

const panel = useAuthPanel()
const page = usePage()
const stats = computed(() => (page.props as any).publicStats as { value: string; label: string }[] | undefined)
</script>

<template>
  <div class="min-h-screen bg-green-50 flex flex-col lg:flex-row">
    <!-- Left: brand panel (lg+) -->
    <aside
      class="hidden lg:flex lg:w-[52%] xl:w-[48%] flex-col relative overflow-hidden"
      style="background: linear-gradient(180deg, #0d3b26 0%, #092b1c 40%, #061a11 100%);"
    >
      <div class="absolute -top-32 -left-32 w-[420px] h-[420px] rounded-full border border-white/10" />
      <div class="absolute -top-20 -left-20 w-[300px] h-[300px] rounded-full border border-white/10" />
      <div class="absolute bottom-0 right-0 w-[500px] h-[500px] rounded-full border border-white/5 translate-x-1/3 translate-y-1/3" />
      <div
        class="absolute top-0 right-0 w-28 h-full bg-[#dc2626]/10 origin-top-right"
        style="clip-path: polygon(100% 0, 100% 100%, 60% 100%, 20% 0)"
      />

      <div class="relative z-10 flex flex-col h-full px-8 xl:px-12 py-8 xl:py-10 overflow-y-auto">
        <div class="flex items-center gap-3">
          <BrandMark status />
          <div>
            <p class="font-black text-xl tracking-widest leading-none" style="color: #e8c44a; text-shadow: 0 1px 4px rgba(232,196,74,0.4);">PRP</p>
            <p class="font-bold text-[10px] uppercase mt-1 tracking-[0.25em]" style="color: rgba(255,255,255,0.55);">Bauchi State</p>
          </div>
        </div>

        <div class="mt-auto mb-auto pt-14 xl:pt-20">
          <div class="inline-block border rounded-full px-3 py-1 mb-6" style="background: rgba(232,196,74,0.1); border-color: rgba(232,196,74,0.25);">
            <span class="text-xs font-semibold uppercase tracking-widest" style="color: #e8c44a;">{{ panel.badge }}</span>
          </div>

          <h1 class="text-white font-black text-3xl xl:text-5xl leading-[1.05] tracking-tight max-w-sm">
            <template v-for="(line, i) in panel.lines" :key="i">
              <span :style="line.accent ? 'color: #4ade80;' : ''">{{ line.text }}</span><br v-if="i < panel.lines.length - 1" />
            </template>
          </h1>

          <p class="text-white/60 text-sm mt-5 max-w-xs leading-relaxed">{{ panel.text }}</p>

          <ol v-if="panel.steps?.length" class="mt-8 space-y-3.5">
            <li v-for="(step, i) in panel.steps" :key="i" class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-full border flex items-center justify-center flex-shrink-0 text-[10px] font-black" style="background: rgba(232,196,74,0.1); border-color: rgba(232,196,74,0.35); color: #e8c44a;">{{ i + 1 }}</span>
              <span class="text-white/70 text-sm font-medium">{{ step }}</span>
            </li>
          </ol>
        </div>

        <div v-if="stats?.length" class="flex flex-wrap gap-6 mt-auto pb-2">
          <div v-for="stat in stats" :key="stat.label">
            <p class="text-white font-black text-xl leading-none">{{ stat.value }}</p>
            <p class="text-white/40 text-[11px] mt-0.5 uppercase tracking-wider">{{ stat.label }}</p>
          </div>
        </div>

        <p class="text-white/25 text-[10px] font-medium uppercase tracking-widest mt-5 border-t border-white/10 pt-4">
          People's Redemption Party · Bauchi State Chapter
        </p>
      </div>
    </aside>

    <!-- Right: form -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 sm:px-8 lg:px-12 xl:px-16 py-8 sm:py-12">
      <div class="flex lg:hidden items-center gap-3 mb-8 sm:mb-10">
        <BrandMark size="sm" />
        <div>
          <p class="font-black text-lg leading-none" style="color: #0d3b26;">PRP</p>
          <p class="text-[#16a34a] text-[9px] font-bold uppercase tracking-[0.2em] mt-0.5">Bauchi State</p>
        </div>
      </div>

      <div class="w-full max-w-sm">
        <slot />
      </div>

      <p class="mt-8 sm:mt-auto pt-6 sm:pt-10 text-[11px] text-[#9ca3af] text-center px-4">
        © {{ new Date().getFullYear() }} PRP Bauchi State Management System ·
        <span class="text-[#6b7280]">Smart Core ICT Service Consultant</span>
      </p>
    </main>
  </div>
</template>