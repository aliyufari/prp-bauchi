<script setup lang="ts">
import { ChevronRight, Home } from 'lucide-vue-next'
export interface BreadcrumbItem { label: string; href?: string }
defineProps<{ items: BreadcrumbItem[]; homeHref?: string }>()
</script>
<template>
  <nav aria-label="Breadcrumb" class="min-w-0">
    <ol class="flex flex-wrap items-center gap-1.5 text-xs text-[#6b7280]">
      <li><a :href="homeHref || '/dashboard'" aria-label="Home" class="inline-flex items-center hover:text-[#0d3b26]"><Home class="h-3.5 w-3.5" /></a></li>
      <li v-for="(item, index) in items" :key="`${item.label}-${index}`" class="inline-flex min-w-0 items-center gap-1.5">
        <ChevronRight class="h-3.5 w-3.5 shrink-0 text-[#9ca3af]" />
        <a v-if="item.href && index < items.length - 1" :href="item.href" class="truncate hover:text-[#0d3b26]">{{ item.label }}</a>
        <span v-else class="truncate font-medium text-[#374151]" :aria-current="index === items.length - 1 ? 'page' : undefined">{{ item.label }}</span>
      </li>
    </ol>
  </nav>
</template>
