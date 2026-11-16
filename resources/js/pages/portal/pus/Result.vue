<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Trash2, UploadCloud } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Textarea from '@/components/ui/form/Textarea.vue'
import ConfirmDialog from '@/components/ui/overlay/ConfirmDialog.vue'
import EvidenceUpload from '@/components/pu/EvidenceUpload.vue'
import PuHeader from '@/components/pu/PuHeader.vue'
import TaskStatus from '@/components/pu/TaskStatus.vue'
import { useToast } from '@/composables/useToast'

interface Sheet { index: number; name: string; url: string }
const props = defineProps<{
  office: string
  officeLabel: string
  pu: { id: string; name: string; code: string; ward: string | null; lga: string | null }
  submission: { video: { url: string; name: string | null } | null; sheets: Sheet[]; remarks: string | null; updated_at: string | null; submitted_by: string | null } | null
  limits: { video_mb: number; sheet_mb: number; max_sheets: number }
}>()

const toast = useToast()
const form = useForm<{ video: File[]; sheets: File[]; remarks: string }>({
  video: [], sheets: [], remarks: props.submission?.remarks ?? '',
})

const room = computed(() => props.limits.max_sheets - (props.submission?.sheets.length ?? 0))
const hasSavedVideo = computed(() => Boolean(props.submission?.video))
const nothingToSend = computed(() => !form.video.length && !form.sheets.length && form.remarks === (props.submission?.remarks ?? ''))

function submit() {
  form
    .transform((d) => ({ video: d.video[0] ?? undefined, sheets: d.sheets, remarks: d.remarks }))
    .post(route('portal.pus.result.save', { pu: props.pu.id, office: props.office }), { forceFormData: true, preserveScroll: true })
}

const toDelete = ref<{ kind: 'video' | 'sheet'; index?: number } | null>(null)
const deleting = ref(false)
function confirmDelete() {
  if (!toDelete.value) return
  deleting.value = true
  router.delete(route('portal.pus.result.file', { pu: props.pu.id, office: props.office }), {
    data: toDelete.value, preserveScroll: true,
    onFinish: () => { deleting.value = false; toDelete.value = null },
  })
}
</script>

<template>
  <Head title="Upload result" />

  <PuHeader :pu="pu" title="Result evidence" :subtitle="`${officeLabel} election. Record the Presiding Officer counting and announcing the result, then photograph the signed result sheet.`" />

  <form class="space-y-5" @submit.prevent="submit">
    <!-- 1. Video -->
    <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
      <div class="mb-3 flex items-center justify-between gap-2">
        <h2 class="text-sm font-black text-neutral-900">1. Counting &amp; announcement video</h2>
        <TaskStatus :done="hasSavedVideo" done-text="Uploaded" pending-text="Required" />
      </div>

      <div v-if="submission?.video" class="mb-4">
        <video :src="submission.video.url" controls playsinline preload="metadata" class="aspect-video w-full rounded-xl bg-black" />
        <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-red-700 hover:underline" @click="toDelete = { kind: 'video' }">
          <Trash2 class="h-3.5 w-3.5" /> Remove this video
        </button>
        <p class="mt-3 text-xs font-semibold text-neutral-500">Record again below to replace it.</p>
      </div>

      <EvidenceUpload
        v-model="form.video" kind="video" :max-mb="limits.video_mb" :error="form.errors.video"
        :disabled="form.processing" @reject="toast.error"
      />
    </section>

    <!-- 2. Result sheet photos -->
    <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
      <div class="mb-3 flex items-center justify-between gap-2">
        <h2 class="text-sm font-black text-neutral-900">2. Result sheet photos</h2>
        <TaskStatus :done="(submission?.sheets.length ?? 0) > 0" :done-text="`${submission?.sheets.length} saved`" pending-text="Required" />
      </div>

      <ul v-if="submission?.sheets.length" class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
        <li v-for="s in submission.sheets" :key="s.index" class="relative overflow-hidden rounded-xl border border-neutral-200">
          <a :href="s.url" target="_blank" rel="noopener"><img :src="s.url" :alt="s.name" class="aspect-[3/4] w-full object-cover" loading="lazy" /></a>
          <button
            type="button" aria-label="Remove photo"
            class="absolute right-1.5 top-1.5 rounded-full bg-neutral-900/80 p-1.5 text-white hover:bg-red-600"
            @click="toDelete = { kind: 'sheet', index: s.index }"
          ><Trash2 class="h-3.5 w-3.5" /></button>
        </li>
      </ul>

      <EvidenceUpload
        v-if="room > 0" v-model="form.sheets" kind="image" multiple :max-mb="limits.sheet_mb" :max-files="room"
        :error="form.errors.sheets || (form.errors as Record<string, string>)['sheets.0'] || null"
        :disabled="form.processing" @reject="toast.error"
      />
      <p v-else class="text-xs font-semibold text-neutral-500">The maximum of {{ limits.max_sheets }} photos is reached. Remove one to add another.</p>
    </section>

    <!-- 3. Remarks -->
    <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
      <Textarea v-model="form.remarks" label="Remarks (optional)" placeholder="Anything we should know about this polling unit" :error="form.errors.remarks" :rows="3" />
    </section>

    <div v-if="form.processing && form.progress" class="rounded-xl border border-neutral-200 bg-white p-4">
      <div class="mb-1.5 flex justify-between text-xs font-bold text-neutral-700">
        <span>Uploading… keep this page open</span><span>{{ form.progress.percentage }}%</span>
      </div>
      <div class="h-2 overflow-hidden rounded-full bg-neutral-200">
        <div class="h-full rounded-full bg-red-600 transition-all" :style="{ width: `${form.progress.percentage}%` }" />
      </div>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <Button variant="neutral" :href="route('portal.pus', { office })" block class="sm:w-auto">Back</Button>
      <Button type="submit" size="lg" :loading="form.processing" :disabled="nothingToSend" block class="sm:w-auto">
        <UploadCloud class="h-4 w-4" /> Upload to PRP
      </Button>
    </div>
  </form>

  <ConfirmDialog
    :open="Boolean(toDelete)" title="Remove this file?" description="It will be deleted from the server. You can upload it again afterwards."
    confirm-text="Remove" :loading="deleting" @confirm="confirmDelete" @cancel="toDelete = null" @close="toDelete = null"
  />
</template>
