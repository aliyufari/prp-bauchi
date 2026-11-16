<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import banner from '@/assets/elections.jpg';
import logoUrl from '@/assets/logo.jpg';
import aspirantImage from '@/assets/images/governor-aspirant.jpeg';
import {
  ArrowRight,
  ShieldCheck,
  Users,
  BarChart3,
  MapPin,
  Quote,
  CheckCircle2,
} from 'lucide-vue-next';

const features = [
  {
    icon: Users,
    tag: '01 · Registry',
    title: 'Member Management',
    desc: 'Structured, deduplicated directory of registered party faithfuls across every ward and polling unit.',
  },
  {
    icon: MapPin,
    tag: '02 · Field Ops',
    title: '20-LGA Coverage',
    desc: 'Real-time coordinator reporting and agent deployment tracking across all 20 local government areas.',
  },
  {
    icon: BarChart3,
    tag: '03 · Audit Trail',
    title: 'Election Data Integrity',
    desc: 'Transparent result collation, timestamped submissions, and multi-tier verification workflows.',
  },
];

const metrics = [
  { value: '20', label: 'LGAs Covered' },
  { value: '323', label: 'Wards Connected' },
  { value: '24/7', label: 'Live Monitoring' },
];
</script>

<template>
  <Head title="Welcome – PRP Bauchi State" />

  <div class="flex min-h-screen flex-col bg-white text-neutral-900 antialiased selection:bg-red-600 selection:text-white">
    <!-- Subtle PRP Tri-Colour Accent Bar (Red · White · Black) -->
    <div class="flex h-1 w-full" aria-hidden="true">
      <span class="flex-1 bg-red-600"></span>
      <span class="flex-1 bg-neutral-200"></span>
      <span class="flex-1 bg-neutral-950"></span>
    </div>

    <!-- Navigation Header -->
    <header class="sticky top-0 z-30 border-b border-neutral-200/80 bg-white/90 backdrop-blur-md">
      <div class="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <Link href="/" class="group flex items-center gap-3.5 focus:outline-none">
          <div class="relative h-10 w-10 shrink-0 overflow-hidden rounded-full border border-neutral-200 bg-white p-0.5 shadow-xs transition group-hover:border-red-600">
            <img
              v-if="logoUrl"
              :src="logoUrl"
              alt="People's Redemption Party Logo"
              class="h-full w-full rounded-full object-cover"
            />
            <ShieldCheck v-else class="m-2 h-5 w-5 text-red-600" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-base font-black tracking-wider text-neutral-950">PRP</span>
              <span class="h-3.5 w-px bg-neutral-300" aria-hidden="true"></span>
              <span class="text-xs font-bold uppercase tracking-[0.18em] text-red-600">Bauchi State</span>
            </div>
            <p class="hidden text-[11px] font-medium text-neutral-500 sm:block">
              People's Redemption Party · Digital Command
            </p>
          </div>
        </Link>

        <nav class="flex items-center gap-3 sm:gap-5">
          <template v-if="$page.props.auth.user">
            <Link
              :href="route('dashboard')"
              class="inline-flex items-center gap-2 rounded-lg bg-neutral-950 px-4 py-2.5 text-xs font-bold tracking-wide text-white shadow-xs transition hover:bg-red-600 sm:px-5 sm:text-sm"
            >
              <span>Access Dashboard</span>
              <ArrowRight class="h-4 w-4" />
            </Link>
          </template>
          <template v-else>
            <Link
              :href="route('login')"
              class="rounded-lg px-3 py-2 text-xs font-bold text-neutral-700 transition hover:bg-neutral-100 hover:text-neutral-950 sm:text-sm"
            >
              Sign In
            </Link>
            <Link
              :href="route('register')"
              class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2.5 text-xs font-bold tracking-wide text-white shadow-xs transition hover:bg-red-700 sm:px-5 sm:text-sm"
            >
              <span>Request Access</span>
              <ArrowRight class="hidden h-3.5 w-3.5 sm:inline" />
            </Link>
          </template>
        </nav>
      </div>
    </header>

    <main class="flex-1">
      <!-- Hero Section: Clean Light Canvas with Disciplined Red & Black Accents -->
      <section class="relative isolate overflow-hidden border-b border-neutral-200/80 bg-neutral-50">
        <!-- Faint watermark texture + subtle radial highlight -->
        <div
          class="pointer-events-none absolute inset-0 -z-10 bg-cover bg-center opacity-[0.035] grayscale"
          :style="{ backgroundImage: `url(${banner})` }"
          aria-hidden="true"
        ></div>
        <div
          class="pointer-events-none absolute -top-40 right-0 -z-10 h-[32rem] w-[32rem] rounded-full bg-red-600/[0.06] blur-3xl"
          aria-hidden="true"
        ></div>

        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:py-24">
          <!-- Left Column: Copy & Actions -->
          <div class="animate-fade-in lg:col-span-7">
            <div class="inline-flex items-center gap-2 rounded-full border border-red-200 bg-red-50/90 px-3.5 py-1.5">
              <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>
              <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-red-700">
                Official State Portal · 2026
              </span>
            </div>

            <h1 class="mt-6 text-4xl font-black leading-[1.08] tracking-tight text-neutral-950 sm:text-5xl lg:text-[3.5rem]">
              Justice, Unity &amp;
              <span class="block text-red-600">Progress for Bauchi.</span>
            </h1>

            <p class="mt-6 max-w-2xl text-base leading-relaxed text-neutral-600 sm:text-lg">
              The centralized digital command center for the <strong class="font-semibold text-neutral-900">People's Redemption Party</strong> — streamlining member registration, field coordination, and verified election reporting across all 20 Local Government Areas.
            </p>

            <!-- Primary & Secondary CTA -->
            <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
              <Link
                v-if="!$page.props.auth.user"
                :href="route('register')"
                class="group inline-flex min-h-12 items-center justify-center gap-2.5 rounded-xl bg-red-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-red-700"
              >
                <span>Join the Movement</span>
                <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
              </Link>

              <Link
                v-else
                :href="route('dashboard')"
                class="group inline-flex min-h-12 items-center justify-center gap-2.5 rounded-xl bg-red-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-red-700"
              >
                <span>Go to Dashboard</span>
                <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
              </Link>

              <a
                href="#leadership"
                class="inline-flex min-h-12 items-center justify-center rounded-xl border border-neutral-300 bg-white px-6 py-3.5 text-sm font-bold text-neutral-900 transition hover:border-neutral-950 hover:bg-neutral-950 hover:text-white"
              >
                Meet Our Aspirant
              </a>
            </div>

            <!-- Key Operational Metrics -->
            <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-neutral-200 pt-8">
              <div v-for="stat in metrics" :key="stat.label">
                <dt class="text-[11px] font-bold uppercase tracking-wider text-neutral-500">
                  {{ stat.label }}
                </dt>
                <dd class="mt-1 text-2xl font-black tracking-tight text-neutral-950 sm:text-3xl">
                  {{ stat.value }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Right Column: Editorial Aspirant Portrait Card -->
          <div class="animate-fade-in lg:col-span-5">
            <div class="mx-auto max-w-md lg:max-w-none">
              <div class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-900 shadow-xl">
                <div class="aspect-[4/5] w-full overflow-hidden">
                  <img
                    :src="aspirantImage"
                    alt="PRP Governorship Aspirant – Bauchi State"
                    class="h-full w-full object-cover object-top transition duration-700 hover:scale-[1.02]"
                  />
                </div>

                <!-- Clean Dark Scrim Caption -->
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-neutral-950 via-neutral-950/80 to-transparent p-6 pt-20">
                  <div class="flex items-center gap-2">
                    <span class="h-0.5 w-6 bg-red-500"></span>
                    <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-400">
                      Governorship Aspirant
                    </span>
                  </div>
                  <p class="mt-1.5 text-xl font-bold tracking-tight text-white">
                    Leading Bauchi Forward
                  </p>
                  <p class="mt-1 flex items-center gap-1.5 text-xs text-neutral-300">
                    <CheckCircle2 class="h-3.5 w-3.5 text-red-500" />
                    <span>People's Redemption Party · Bauchi 2026</span>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Leadership Statement Section -->
      <section id="leadership" class="scroll-mt-20 bg-white px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <div class="mx-auto mb-6 flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600">
            <Quote class="h-5 w-5" />
          </div>
          <blockquote class="text-xl font-bold leading-relaxed tracking-tight text-neutral-900 sm:text-2xl lg:text-[1.75rem]">
            “Our commitment is to a Bauchi State built on justice, transparency, and grassroots progress — powered by the people, for the people.”
          </blockquote>
          <div class="mt-6 flex items-center justify-center gap-3">
            <span class="h-px w-8 bg-neutral-300"></span>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-red-600">
              PRP Governorship Aspirant · Bauchi State
            </p>
            <span class="h-px w-8 bg-neutral-300"></span>
          </div>
        </div>
      </section>

      <!-- Platform Pillars -->
      <section class="border-t border-neutral-200/80 bg-neutral-50 px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
        <div class="mx-auto max-w-7xl">
          <div class="mb-12 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.2em] text-red-600">Platform Capabilities</p>
              <h2 class="mt-2 text-2xl font-black tracking-tight text-neutral-950 sm:text-3xl">
                Built for Grassroots Accountability
              </h2>
            </div>
            <p class="max-w-md text-sm text-neutral-600">
              Every ward coordinator, polling unit agent, and state stakeholder operates from one unified source of truth.
            </p>
          </div>

          <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <article
              v-for="feature in features"
              :key="feature.title"
              class="group relative rounded-2xl border border-neutral-200 bg-white p-7 shadow-2xs transition duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md"
            >
              <div class="flex items-center justify-between">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-neutral-950 text-white transition-colors group-hover:bg-red-600">
                  <component :is="feature.icon" class="h-5 w-5" />
                </div>
                <span class="text-[11px] font-bold uppercase tracking-widest text-neutral-400">
                  {{ feature.tag }}
                </span>
              </div>

              <h3 class="mt-6 text-lg font-bold text-neutral-950">
                {{ feature.title }}
              </h3>
              <p class="mt-2 text-sm leading-relaxed text-neutral-600">
                {{ feature.desc }}
              </p>
            </article>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-neutral-800 bg-neutral-950 text-neutral-400">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 sm:px-6 md:flex-row lg:px-8">
        <div class="flex items-center gap-2.5 text-xs">
          <span class="inline-block h-2 w-2 rounded-full bg-red-600"></span>
          <p class="font-medium tracking-wide text-neutral-400">
            © 2026 People's Redemption Party (PRP) Bauchi State ·
            <span class="font-semibold text-neutral-200">Smart Core ICT Service</span>
          </p>
        </div>
        <div class="flex items-center gap-6 text-xs font-semibold">
          <a href="#" class="transition-colors hover:text-white">Privacy Policy</a>
          <a href="#" class="transition-colors hover:text-white">Terms of Service</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-fade-in {
  animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>