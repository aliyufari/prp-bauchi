<script setup lang="ts">
/** Four simple, independent tasks for a polling-unit agent. */
import { Link } from '@inertiajs/vue3'
import { ArrowRight, Camera, CheckCircle2, ClipboardList, FileSpreadsheet, Video } from 'lucide-vue-next'

const props = defineProps<{
  unit: { id: string; officials_done: boolean; has_video: boolean; sheet_count: number; has_figures: boolean }
  office: string
}>()

const tasks = () => [
  {
    number: 1,
    title: 'Manage PU',
    description: 'Add or update the PO, APO1, APO2 and Security names.',
    done: props.unit.officials_done,
    status: props.unit.officials_done ? 'Names saved' : 'Names not submitted',
    href: route('portal.pus.officials', { pu: props.unit.id, office: props.office }),
    icon: ClipboardList,
  },
  {
    number: 2,
    title: 'Record counting video',
    description: 'Record the PO counting the votes and presenting the result.',
    done: props.unit.has_video,
    status: props.unit.has_video ? 'Video saved' : 'Video not submitted',
    href: route('portal.pus.video', { pu: props.unit.id, office: props.office }),
    icon: Video,
  },
  {
    number: 3,
    title: 'Upload result-sheet photo',
    description: 'Take a clear photo or choose the result sheet from your device.',
    done: props.unit.sheet_count > 0,
    status: props.unit.sheet_count > 0 ? `${props.unit.sheet_count} photo(s) saved` : 'Photo not submitted',
    href: route('portal.pus.sheet', { pu: props.unit.id, office: props.office }),
    icon: Camera,
  },
  {
    number: 4,
    title: 'Enter party figures',
    description: 'Enter the votes for each party. You can scan the result sheet to help fill the numbers.',
    done: props.unit.has_figures,
    status: props.unit.has_figures ? 'Figures submitted' : 'Figures not submitted',
    href: route('portal.pus.figures', { pu: props.unit.id, office: props.office }),
    icon: FileSpreadsheet,
  },
]
</script>

<template>
  <div class="space-y-3">
    <Link
      v-for="task in tasks()" :key="task.number" :href="task.href"
      class="flex items-center gap-4 rounded-2xl border-2 bg-white p-4 shadow-sm transition active:scale-[0.99]"
      :class="task.done ? 'border-neutral-900' : 'border-red-600'"
    >
      <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl" :class="task.done ? 'bg-neutral-900 text-white' : 'bg-red-600 text-white'">
        <CheckCircle2 v-if="task.done" class="h-7 w-7" />
        <component :is="task.icon" v-else class="h-7 w-7" />
      </span>
      <span class="min-w-0 flex-1">
        <span class="block text-base font-black text-neutral-900">{{ task.number }}. {{ task.title }}</span>
        <span class="block text-sm text-neutral-600">{{ task.description }}</span>
        <span class="mt-1 block text-xs font-bold" :class="task.done ? 'text-neutral-900' : 'text-red-700'">{{ task.status }}</span>
      </span>
      <ArrowRight class="h-5 w-5 shrink-0 text-neutral-400" />
    </Link>
  </div>
</template>
