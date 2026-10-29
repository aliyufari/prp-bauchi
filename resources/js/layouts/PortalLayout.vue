<script setup lang="ts">
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { LogOut } from 'lucide-vue-next'
import { resolveIcon } from '@/lib/icons'
import { usePermissions } from '@/composables/usePermissions'
import { useScopeLabel } from '@/composables/useScopeLabel'
import logoUrl from '@/assets/logo.jpg'
import type { NavItem } from '@/types/navigation'

const page = usePage()
const { auth } = usePermissions()
const { fullName, initials, roleLabel, scopeName } = useScopeLabel()

const items = computed(() => (auth.value.navigation ?? []).flatMap((g) => g.items))

function isActive(item: NavItem) {
  const path = page.url.split('?')[0]
  return item.exact ? path === item.href : path === item.href || path.startsWith(item.href + '/')
}

const logout = () => router.post(route('logout'))
</script>

<template>
  <div class="flex min-h-screen flex-col bg-green-50">
    <header class="sticky top-0 z-30 border-b border-white/10" style="background: linear-gradient(90deg, #0d3b26, #092b1c);">
      <div class="mx-auto flex max-w-3xl items-center gap-3 px-4 py-3">
        <img :src="logoUrl" alt="PRP" class="h-8 w-8 rounded-full object-cover ring-2 ring-[#e8c44a]/40" />

        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-bold text-white">{{ fullName }}</p>
          <p class="truncate text-[11px] text-white/60">
            {{ roleLabel }}<span v-if="scopeName"> · {{ scopeName }}</span>
          </p>
        </div>

        <nav class="hidden items-center gap-1 sm:flex">
          <Link
            v-for="item in items" :key="item.href" :href="item.href"
            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
            :class="isActive(item) ? 'bg-green-600 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white'"
          >{{ item.label }}</Link>
        </nav>

        <button type="button" aria-label="Sign out" class="rounded-lg p-2 text-white/70 transition hover:bg-white/10 hover:text-white" @click="logout">
          <LogOut class="h-4 w-4" />
        </button>
      </div>
    </header>

    <main class="mx-auto w-full max-w-3xl flex-1 space-y-4 px-4 pb-24 pt-4 sm:pb-8">
      <slot />
    </main>

    <!-- Mobile tab bar -->
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] sm:hidden">
      <ul class="mx-auto grid max-w-md" :style="{ gridTemplateColumns: `repeat(${Math.min(items.length, 5) || 1}, minmax(0, 1fr))` }">
        <li v-for="item in items.slice(0, 5)" :key="item.href">
          <Link
            :href="item.href"
            class="flex flex-col items-center gap-0.5 py-2 text-[10px] font-semibold"
            :class="isActive(item) ? 'text-green-700' : 'text-gray-400'"
          >
            <component :is="resolveIcon(item.icon)" class="h-5 w-5" />
            {{ item.label }}
          </Link>
        </li>
      </ul>
    </nav>
  </div>
</template>