<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Trash2, UploadCloud } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import ConfirmDialog from '@/components/ui/overlay/ConfirmDialog.vue'
import EvidenceUpload from '@/components/pu/EvidenceUpload.vue'
import CameraRecorder from '@/components/pu/CameraRecorder.vue'
import PuHeader from '@/components/pu/PuHeader.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps<{
  pu: { id: string; name: string; code: string; ward: string | null; lga: string | null }
  office: string
  submission: { video: { url: string; name: string | null } | null } | null
  limits: { video_mb: number }
}>()

const toast = useToast()
const form = useForm<{ video: File[] }>({ video: [] })

function submit() {
  form
    .transform((d) => ({ video: d.video[0] ?? undefined }))
    .post(route('portal.pus.video.save', { pu: props.pu.id, office: props.office }), { forceFormData: true, preserveScroll: true })
}

const confirming = ref(false)
const deleting = ref(false)
function confirmDelete() {
  deleting.value = true
  router.delete(route('portal.pus.result.file', { pu: props.pu.id, office: props.office }), {
    data: { kind: 'video' }, preserveScroll: true,
    onFinish: () => { deleting.value = false; confirming.value = false },
  })
}
</script>

<template>
  <Head title="Counting video" />

  <PuHeader :pu="pu" :office="office" title="Counting video" subtitle="Record the PO counting the votes and reading out the result. Show the result sheet at the end." />

  <section v-if="submission?.video" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h2 class="mb-3 text-base font-black text-neutral-900">Video already sent</h2>
    <video :src="submission.video.url" controls playsinline preload="metadata" class="aspect-video w-full rounded-xl bg-black" />
    <button type="button" class="mt-3 inline-flex items-center gap-1 text-sm font-bold text-red-700 hover:underline" @click="confirming = true">
      <Trash2 class="h-4 w-4" /> Remove this video
    </button>
  </section>

  <form class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
    <h2 class="text-base font-black text-neutral-900">{{ submission?.video ? 'Send a new video (replaces the old one)' : 'Add the video' }}</h2>

    <CameraRecorder :max-mb="limits.video_mb" :disabled="form.processing" @recorded="form.video = [$event]" />

    <EvidenceUpload v-model="form.video" kind="video" :max-mb="limits.video_mb" :error="form.errors.video" :disabled="form.processing" @reject="toast.error" />

    <div v-if="form.processing && form.progress" class="space-y-1.5">
      <div class="flex justify-between text-sm font-bold text-neutral-700"><span>Sending… keep this page open</span><span>{{ form.progress.percentage }}%</span></div>
      <div class="h-3 overflow-hidden rounded-full bg-neutral-200"><div class="h-full rounded-full bg-red-600 transition-all" :style="{ width: `${form.progress.percentage}%` }" /></div>
    </div>

    <Button type="submit" size="lg" block :loading="form.processing" :disabled="!form.video.length">
      <UploadCloud class="h-5 w-5" /> Send video
    </Button>
  </form>

  <ConfirmDialog
    :open="confirming" title="Remove this video?" description="You can send it again afterwards."
    confirm-text="Remove" :loading="deleting" @confirm="confirmDelete" @cancel="confirming = false" @close="confirming = false"
  />
</template>
