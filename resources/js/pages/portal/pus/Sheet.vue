<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Trash2, UploadCloud } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import ConfirmDialog from '@/components/ui/overlay/ConfirmDialog.vue'
import EvidenceUpload from '@/components/pu/EvidenceUpload.vue'
import PuHeader from '@/components/pu/PuHeader.vue'
import { useToast } from '@/composables/useToast'

interface Sheet { index: number; name: string; url: string }
const props = defineProps<{
  pu: { id: string; name: string; code: string; ward: string | null; lga: string | null }
  office: string
  submission: { sheets: Sheet[] } | null
  limits: { sheet_mb: number; max_sheets: number }
}>()

const toast = useToast()
const saved = () => props.submission?.sheets ?? []
const form = useForm<{ sheets: File[] }>({ sheets: [] })

function submit() {
  form.post(route('portal.pus.sheet.save', { pu: props.pu.id, office: props.office }), { forceFormData: true, preserveScroll: true })
}

const toDelete = ref<number | null>(null)
const deleting = ref(false)
function confirmDelete() {
  if (toDelete.value === null) return
  deleting.value = true
  router.delete(route('portal.pus.result.file', { pu: props.pu.id, office: props.office }), {
    data: { kind: 'sheet', index: toDelete.value }, preserveScroll: true,
    onFinish: () => { deleting.value = false; toDelete.value = null },
  })
}
</script>

<template>
  <Head title="Result sheet" />

  <PuHeader :pu="pu" :office="office" title="Result sheet" subtitle="Take a clear photo of the result sheet. Make sure all the numbers can be read." />

  <section v-if="saved().length" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
    <h2 class="mb-3 text-base font-black text-neutral-900">Photos already sent ({{ saved().length }})</h2>
    <ul class="grid grid-cols-2 gap-2 sm:grid-cols-3">
      <li v-for="s in saved()" :key="s.index" class="relative overflow-hidden rounded-xl border border-neutral-200">
        <a :href="s.url" target="_blank" rel="noopener"><img :src="s.url" :alt="s.name" class="aspect-[3/4] w-full object-cover" loading="lazy" /></a>
        <button type="button" aria-label="Remove photo" class="absolute right-1.5 top-1.5 rounded-full bg-neutral-900/85 p-2 text-white hover:bg-red-600" @click="toDelete = s.index">
          <Trash2 class="h-4 w-4" />
        </button>
      </li>
    </ul>
  </section>

  <form class="space-y-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
    <h2 class="text-base font-black text-neutral-900">{{ saved().length ? 'Add another photo' : 'Add the photo' }}</h2>

    <EvidenceUpload
      v-if="saved().length < limits.max_sheets"
      v-model="form.sheets" kind="image" multiple :max-mb="limits.sheet_mb" :max-files="limits.max_sheets - saved().length"
      :error="form.errors.sheets || (form.errors as Record<string, string>)['sheets.0'] || null"
      :disabled="form.processing" @reject="toast.error"
    />
    <p v-else class="text-sm font-semibold text-neutral-600">You have sent the most photos allowed. Remove one to add another.</p>

    <div v-if="form.processing && form.progress" class="space-y-1.5">
      <div class="flex justify-between text-sm font-bold text-neutral-700"><span>Sending… please wait</span><span>{{ form.progress.percentage }}%</span></div>
      <div class="h-3 overflow-hidden rounded-full bg-neutral-200"><div class="h-full rounded-full bg-red-600 transition-all" :style="{ width: `${form.progress.percentage}%` }" /></div>
    </div>

    <Button type="submit" size="lg" block :loading="form.processing" :disabled="!form.sheets.length">
      <UploadCloud class="h-5 w-5" /> Send photo
    </Button>
  </form>

  <ConfirmDialog
    :open="toDelete !== null" title="Remove this photo?" description="You can send it again afterwards."
    confirm-text="Remove" :loading="deleting" @confirm="confirmDelete" @cancel="toDelete = null" @close="toDelete = null"
  />
</template>
