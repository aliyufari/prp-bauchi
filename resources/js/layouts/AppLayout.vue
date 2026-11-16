<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Sidebar from '@/components/layout/Sidebar.vue'
import Topbar from '@/components/dashboard/Topbar.vue'
import { useScopeLabel } from '@/composables/useScopeLabel'


const { fullName, roleLabel, scopeName } = useScopeLabel()
const page = usePage()
const user = computed(() => ({
  name: fullName.value,
  role: scopeName.value ? `${roleLabel.value} · ${scopeName.value}` : roleLabel.value,
  avatarUrl: (page.props as any).auth?.user?.avatar_url ?? undefined,
}))

const SIDEBAR_STORAGE_KEY = 'prp.sidebar.collapsed'

function readSidebarPreference(): boolean {
  try {
    return window.localStorage.getItem(SIDEBAR_STORAGE_KEY) === 'true'
  } catch {
    return false
  }
}

const sidebarCollapsed = ref(readSidebarPreference()) // desktop collapse (lg+)
const mobileSidebarOpen = ref(false) // mobile drawer (< lg)

watch(sidebarCollapsed, (collapsed) => {
  try {
    window.localStorage.setItem(SIDEBAR_STORAGE_KEY, String(collapsed))
  } catch {
    // Keep the sidebar usable when browser storage is unavailable.
  }
})

function toggleSidebar() {
  if (window.innerWidth >= 1024) sidebarCollapsed.value = !sidebarCollapsed.value
  else mobileSidebarOpen.value = !mobileSidebarOpen.value
}
</script>

<template>
  <!-- App shell: the viewport is fixed. The sidebar and the content area scroll independently,
       so a long page never drags the sidebar along and a short page never leaves it half empty. -->
  <div class="flex h-dvh overflow-hidden bg-neutral-50">
    <Sidebar :collapsed="sidebarCollapsed" :open="mobileSidebarOpen" @close="mobileSidebarOpen = false" />

    <div class="flex h-dvh min-w-0 flex-1 flex-col transition-all duration-300">
      <Topbar :user="user" @toggle-sidebar="toggleSidebar" />

      <!-- `scroll-region` lets Inertia reset this container to the top on navigation. -->
      <div scroll-region class="flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-contain">
        <main class="w-full flex-1 space-y-6 overflow-x-hidden p-3 sm:space-y-8 sm:p-5 lg:p-6">
          <slot />
        </main>

        <footer class="border-t border-gray-100 bg-white px-4 py-4 text-center text-[11px] text-gray-400 sm:px-6 sm:text-xs">
          © {{ new Date().getFullYear() }} PRP Bauchi State Management System |
          Powered by
          <span class="font-bold text-red-700">Smart Core ICT Service Consultant</span>
        </footer>
      </div>
    </div>
  </div>
</template>
