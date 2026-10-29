<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Building2, ChevronRight, X } from 'lucide-vue-next'
import { resolveIcon } from '@/lib/icons'
import { usePermissions } from '@/composables/usePermissions'
import ScopeBadge from './ScopeBadge.vue'
import logoUrl from '@/assets/logo.jpg'
import type { NavItem } from '@/types/navigation'

defineProps<{
  collapsed: boolean
  open?: boolean // mobile drawer visibility
}>()

const emit = defineEmits<{ (e: 'close'): void }>()

const page = usePage()
const { auth } = usePermissions()
const groups = computed(() => auth.value.navigation ?? [])

function isActive(item: NavItem): boolean {
  const path = page.url.split('?')[0]
  return item.exact ? path === item.href : path === item.href || path.startsWith(item.href + '/')
}

const ACTIVE_STYLE = 'background: linear-gradient(90deg,#22c55e,#16a34a); color:#fff; box-shadow: 0 2px 8px rgba(34,197,94,0.35);'

function linkStyle(item: NavItem): string {
  if (isActive(item)) return ACTIVE_STYLE
  return `color: rgba(255,255,255,${item.featured ? '0.75' : '0.65'});`
}

function linkClass(item: NavItem, compact: boolean) {
  return [
    'flex items-center gap-3 px-3 rounded-lg text-sm transition-all duration-200 group',
    item.featured ? 'py-2.5 font-bold' : 'py-2 font-semibold',
    compact ? 'lg:justify-center' : '',
  ]
}
</script>

<template>
  <!-- Mobile backdrop -->
  <Transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="emit('close')"
    />
  </Transition>

  <aside
    :class="[
      'fixed inset-y-0 left-0 z-50 flex flex-col transition-all duration-300 lg:static lg:translate-x-0',
      open ? 'translate-x-0' : '-translate-x-full',
      collapsed ? 'w-60 lg:w-16' : 'w-60',
    ]"
    style="background: linear-gradient(180deg, #0d3b26 0%, #092b1c 40%, #061a11 100%);"
  >
    <!-- Logo -->
    <div
      :class="['flex items-center gap-3 px-4 py-4 border-b', (collapsed && !open) ? 'lg:justify-center' : '']"
      style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.2);"
    >
      <!-- Pro Logo Mark -->
      <div class="relative flex-shrink-0">
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center"
          style="background: linear-gradient(135deg, #e8c44a 0%, #c9960a 50%, #e8c44a 100%); padding: 2px; box-shadow: 0 0 0 2px rgba(232,196,74,0.25), 0 4px 12px rgba(0,0,0,0.4);"
        >
          <div
            class="w-full h-full rounded-full flex items-center justify-center overflow-hidden"
            style="background: linear-gradient(145deg, #0d3b26, #061a11);"
          >
            <img
              v-if="logoUrl"
              :src="logoUrl"
              alt="PRP"
              class="w-full h-full rounded-full object-cover"
            />
            <svg v-else viewBox="0 0 36 36" class="w-7 h-7" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 3 L23 8 L30 8 L30 22 C30 28 18 33 18 33 C18 33 6 28 6 22 L6 8 L13 8 Z"
                fill="none" stroke="#e8c44a" stroke-width="1.2"/>
              <path d="M18 3 L23 8 L13 8 Z" fill="#e8c44a" opacity="0.3"/>
              <text x="18" y="22" text-anchor="middle" font-size="7" font-weight="900"
                font-family="Arial,sans-serif" fill="#e8c44a" letter-spacing="0.5">PRP</text>
              <circle cx="11" cy="13" r="1" fill="#e8c44a"/>
              <circle cx="25" cy="13" r="1" fill="#e8c44a"/>
            </svg>
          </div>
        </div>
        <div
          class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 flex items-center justify-center"
          style="background: #22c55e; border-color: #0d3b26;"
        />
      </div>

      <Transition name="label">
        <div v-if="!collapsed || open" class="min-w-0 overflow-hidden flex-1">
          <p
            class="font-black text-base leading-none tracking-widest"
            style="color: #e8c44a; text-shadow: 0 1px 4px rgba(232,196,74,0.4);"
          >PRP</p>
          <p
            class="font-bold text-[9px] uppercase mt-1 tracking-[0.25em]"
            style="color: rgba(255,255,255,0.55);"
          >Bauchi State</p>
          <ScopeBadge />
        </div>
      </Transition>

      <!-- Close button (mobile only) -->
      <button
        class="lg:hidden p-1.5 rounded-lg flex-shrink-0"
        style="color: rgba(255,255,255,0.6);"
        aria-label="Close menu"
        @click="emit('close')"
      >
        <X class="w-5 h-5" />
      </button>
    </div>

    <!-- Navigation (built by the server from the user's permissions) -->
    <nav class="flex-1 overflow-y-auto py-3 space-y-1 sidebar-scroll">
      <div v-for="(group, gi) in groups" :key="group.group ?? `g${gi}`" class="px-2">
        <template v-if="group.group">
          <p
            v-if="!collapsed || open"
            class="px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.22em]"
            style="color: rgba(255,255,255,0.3);"
          >
            {{ group.group }}
          </p>
          <div
            v-else
            class="mx-2 my-1 border-t"
            style="border-color: rgba(255,255,255,0.08);"
          />
        </template>

        <ul class="space-y-0.5">
          <li v-for="item in group.items" :key="item.label">
            <!-- Built module: real link -->
            <Link
              v-if="item.ready"
              :href="item.href"
              @click="emit('close')"
              :class="linkClass(item, collapsed && !open)"
              :style="linkStyle(item)"
              :title="(collapsed && !open) ? item.label : undefined"
            >
              <component :is="resolveIcon(item.icon)" class="w-4 h-4 flex-shrink-0" />
              <span v-if="!collapsed || open" class="truncate flex-1">{{ item.label }}</span>
              <ChevronRight
                v-if="(!collapsed || open) && !item.featured"
                class="w-3 h-3 ml-auto flex-shrink-0 opacity-30 group-hover:opacity-60 transition-opacity"
              />
            </Link>

            <!-- Planned module: visible but not clickable yet -->
            <div
              v-else
              :class="[linkClass(item, collapsed && !open), 'cursor-default select-none']"
              style="color: rgba(255,255,255,0.32);"
              :title="(collapsed && !open) ? `${item.label} (coming soon)` : 'Coming soon'"
              aria-disabled="true"
            >
              <component :is="resolveIcon(item.icon)" class="w-4 h-4 flex-shrink-0" />
              <span v-if="!collapsed || open" class="truncate flex-1">{{ item.label }}</span>
              <span
                v-if="!collapsed || open"
                class="ml-auto flex-shrink-0 rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.4);"
              >Soon</span>
            </div>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Footer -->
    <div
      :class="['p-3 border-t', (collapsed && !open) ? 'lg:flex lg:justify-center' : '']"
      style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.25);"
    >
      <div :class="['flex items-center gap-3', (collapsed && !open) ? 'lg:justify-center' : 'px-1']">
        <div
          class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
          style="background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.25);"
        >
          <Building2 class="w-4 h-4" style="color: #4ade80;" />
        </div>
        <div v-if="!collapsed || open" class="overflow-hidden">
          <p class="text-xs font-bold truncate" style="color: rgba(255,255,255,0.85);">Smart Core ICT</p>
          <p class="text-[10px] font-medium" style="color: rgba(255,255,255,0.35);">Service Consultant</p>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.label-enter-active,
.label-leave-active {
  transition: opacity 0.2s ease, max-width 0.3s ease;
}
.label-enter-from,
.label-leave-to {
  opacity: 0;
  max-width: 0;
}
.label-enter-to,
.label-leave-from {
  opacity: 1;
  max-width: 200px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.sidebar-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.1) transparent;
}
.sidebar-scroll::-webkit-scrollbar {
  width: 3px;
}
.sidebar-scroll::-webkit-scrollbar-thumb {
  background-color: rgba(255,255,255,0.1);
  border-radius: 4px;
}

a:not([style*="linear-gradient"]):hover {
  background: rgba(255,255,255,0.07) !important;
  color: rgba(255,255,255,0.95) !important;
}
</style>