<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import BrandMark from '@/components/auth/BrandMark.vue';
import { useAuthPanel } from '@/composables/useAuthPanel';

const panel = useAuthPanel();
const page = usePage();
const stats = computed(() => (page.props as any).publicStats as { value: string; label: string }[] | undefined);
</script>

<template>
  <div class="no-scrollbar flex min-h-screen flex-col bg-neutral-50 text-neutral-900 antialiased selection:bg-red-600 selection:text-white lg:flex-row">
    <!-- Left: Sticky Brand & Context Panel (lg+) -->
    <aside
      class="no-scrollbar relative hidden flex-col justify-between overflow-y-auto border-r border-neutral-800 bg-neutral-950 lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-[46%] xl:w-[44%]"
    >
      <!-- Top PRP Tri-Colour Accent Bar -->
      <div class="flex h-1 w-full shrink-0" aria-hidden="true">
        <span class="flex-1 bg-red-600"></span>
        <span class="flex-1 bg-white/90"></span>
        <span class="flex-1 bg-neutral-800"></span>
      </div>

      <!-- Subtle Architectural Rings & Glow -->
      <div class="pointer-events-none absolute -left-32 -top-32 h-[26rem] w-[26rem] rounded-full border border-white/[0.07]" aria-hidden="true" />
      <div class="pointer-events-none absolute -left-20 -top-20 h-[18rem] w-[18rem] rounded-full border border-white/[0.07]" aria-hidden="true" />
      <div class="pointer-events-none absolute -bottom-32 -right-32 h-[28rem] w-[28rem] rounded-full bg-red-600/[0.07] blur-3xl" aria-hidden="true" />

      <div class="relative z-10 flex flex-1 flex-col justify-between px-8 py-8 xl:px-12 xl:py-10">
        <!-- Brand Header -->
        <Link href="/" class="group inline-flex w-fit items-center gap-3.5 focus:outline-none">
          <BrandMark status />
          <div>
            <div class="flex items-center gap-2">
              <span class="text-lg font-black tracking-wider text-white transition-colors group-hover:text-red-500">PRP</span>
              <span class="h-3.5 w-px bg-neutral-700" aria-hidden="true"></span>
              <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-500">Bauchi State</span>
            </div>
            <p class="mt-0.5 text-[11px] font-medium text-neutral-400">
              People's Redemption Party · Digital Command
            </p>
          </div>
        </Link>

        <!-- Dynamic Hero Copy & Optional Onboarding Steps -->
        <div class="my-auto py-8">
          <div class="inline-flex items-center gap-2 rounded-full border border-red-500/25 bg-red-500/10 px-3 py-1">
            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
            <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-red-400">
              {{ panel.badge }}
            </span>
          </div>

          <h2 class="mt-5 max-w-md text-3xl font-black leading-[1.1] tracking-tight text-white xl:text-[2.65rem]">
            <template v-for="(line, i) in panel.lines" :key="i">
              <span :class="line.accent ? 'text-red-500' : ''">{{ line.text }}</span>
              <br v-if="i < panel.lines.length - 1" />
            </template>
          </h2>

          <p class="mt-4 max-w-sm text-sm leading-relaxed text-neutral-400">
            {{ panel.text }}
          </p>

          <ol v-if="panel.steps?.length" class="mt-6 space-y-2.5 border-t border-neutral-800/90 pt-5">
            <li v-for="(step, i) in panel.steps" :key="i" class="flex items-start gap-3">
              <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-red-500/35 bg-red-500/10 text-[10px] font-black text-red-400">
                {{ i + 1 }}
              </span>
              <span class="text-[13px] font-medium leading-snug text-neutral-300">{{ step }}</span>
            </li>
          </ol>
        </div>

        <!-- Bottom Stats & Chapter Footer -->
        <div>
          <dl v-if="stats?.length" class="mb-5 flex flex-wrap gap-8 border-t border-neutral-800/90 pt-5">
            <div v-for="stat in stats" :key="stat.label">
              <dd class="text-xl font-black leading-none text-white">{{ stat.value }}</dd>
              <dt class="mt-1 text-[10px] font-bold uppercase tracking-wider text-neutral-400">{{ stat.label }}</dt>
            </div>
          </dl>

          <p class="border-t border-neutral-800/80 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-neutral-500">
            People's Redemption Party · Bauchi State Chapter
          </p>
        </div>
      </div>
    </aside>

    <!-- Right: Cardless Form Column -->
    <main class="no-scrollbar flex min-h-screen min-w-0 flex-1 flex-col bg-neutral-50">
      <!-- Mobile Tri-Colour Strip (lg:hidden) -->
      <div class="flex h-1 w-full lg:hidden" aria-hidden="true">
        <span class="flex-1 bg-red-600"></span>
        <span class="flex-1 bg-neutral-200"></span>
        <span class="flex-1 bg-neutral-950"></span>
      </div>

      <div class="flex flex-1 items-center justify-center px-5 py-8 sm:px-8 lg:px-12 xl:px-16">
        <div class="w-full max-w-md">
          <!-- Mobile Brand Header -->
          <Link href="/" class="mb-8 flex items-center justify-center gap-3 lg:hidden">
            <BrandMark size="sm" />
            <div>
              <div class="flex items-center gap-1.5">
                <span class="text-lg font-black leading-none tracking-wider text-neutral-950">PRP</span>
                <span class="h-3 w-px bg-neutral-300" aria-hidden="true"></span>
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-red-600">Bauchi State</span>
              </div>
            </div>
          </Link>

          <!-- Direct Cardless Form Slot -->
          <slot />
        </div>
      </div>

      <footer class="px-4 pb-6 text-center text-[11px] font-medium text-neutral-400 sm:px-8">
        © {{ new Date().getFullYear() }} PRP Bauchi State Management System ·
        <span class="font-semibold text-neutral-600">Smart Core ICT Service</span>
      </footer>
    </main>
  </div>
</template>

<style scoped>
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>

<style>
html,
body {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

html::-webkit-scrollbar,
body::-webkit-scrollbar {
  display: none;
}
</style>