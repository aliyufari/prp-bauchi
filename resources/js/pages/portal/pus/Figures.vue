<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Camera, CheckCircle2, ImagePlus, LockKeyhole, ScanText, Trash2, Vote } from 'lucide-vue-next'
import { computed, onBeforeUnmount, ref, watch } from 'vue'

interface Option { value: string; label: string }
interface ElectionOption { id: string; name: string; date: string | null }
interface ExistingResult {
  id: string
  accredited_voters: number
  rejected_votes: number
  party_votes: Record<string, number>
  remarks: string | null
  status: 'pending' | 'verified' | 'disputed'
  updated_at: string | null
}

const props = defineProps<{
  pu: { id: string; name: string; code: string; ward: string | null; lga: string | null; registered_voters: number }
  elections: ElectionOption[]
  selectedElectionId: string | null
  offices: Option[]
  office: string
  parties: Option[]
  result: ExistingResult | null
  canEdit: boolean
}>()

const electionId = ref(props.selectedElectionId ?? '')
const office = ref(props.office)
const form = useForm({
  election_id: electionId.value,
  office: office.value,
  accredited_voters: props.result?.accredited_voters ?? 0,
  rejected_votes: props.result?.rejected_votes ?? 0,
  party_votes: Object.fromEntries(props.parties.map((party) => [party.value, props.result?.party_votes?.[party.value] ?? 0])) as Record<string, number>,
  remarks: props.result?.remarks ?? '',
  result_sheet: null as File | null,
})
const validVotes = computed(() => Object.values(form.party_votes).reduce((sum, value) => sum + (Number(value) || 0), 0))
const totalVotes = computed(() => validVotes.value + Number(form.rejected_votes || 0))
const statusLabel = computed(() => props.result?.status ? props.result.status.charAt(0).toUpperCase() + props.result.status.slice(1) : 'Not submitted')

watch([electionId, office], ([nextElection, nextOffice], [oldElection, oldOffice]) => {
  if (nextElection === oldElection && nextOffice === oldOffice) return
  router.get(route('portal.pus.figures', { pu: props.pu.id }), { election_id: nextElection, office: nextOffice }, { preserveState: true, preserveScroll: true, replace: true })
})
watch(() => [props.selectedElectionId, props.office, props.result], () => {
  electionId.value = props.selectedElectionId ?? ''
  office.value = props.office
  form.defaults({
    election_id: electionId.value,
    office: office.value,
    accredited_voters: props.result?.accredited_voters ?? 0,
    rejected_votes: props.result?.rejected_votes ?? 0,
    party_votes: Object.fromEntries(props.parties.map((party) => [party.value, props.result?.party_votes?.[party.value] ?? 0])) as Record<string, number>,
    remarks: props.result?.remarks ?? '',
    result_sheet: null,
  } as any)
  form.election_id = electionId.value
  form.office = office.value
  form.accredited_voters = props.result?.accredited_voters ?? 0
  form.rejected_votes = props.result?.rejected_votes ?? 0
  form.party_votes = Object.fromEntries(props.parties.map((party) => [party.value, props.result?.party_votes?.[party.value] ?? 0]))
  form.remarks = props.result?.remarks ?? ''
}, { deep: true })

function submit() {
  form.post(route('portal.pus.figures.save', { pu: props.pu.id }), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      setResultSheet(null)
    },
  })
}

const cameraInput = ref<HTMLInputElement | null>(null)
const galleryInput = ref<HTMLInputElement | null>(null)
const resultSheetFile = ref<File | null>(null)
const resultSheetPreview = ref<string | null>(null)
const ocrBusy = ref(false)
const ocrProgress = ref(0)
const ocrError = ref('')
const ocrText = ref('')
const ocrConfidence = ref<number | null>(null)
let tesseractScript: Promise<void> | null = null

type OcrWorker = {
  recognize: (image: File) => Promise<{ data: { text: string; confidence: number } }>
  terminate: () => Promise<void>
}

declare global {
  interface Window {
    Tesseract?: {
      createWorker: (
        language: string,
        oem?: number,
        options?: { logger?: (message: { status?: string; progress?: number }) => void },
      ) => Promise<OcrWorker>
    }
  }
}

function setResultSheet(file: File | null) {
  if (resultSheetPreview.value) URL.revokeObjectURL(resultSheetPreview.value)
  resultSheetFile.value = file
  resultSheetPreview.value = file ? URL.createObjectURL(file) : null
  form.result_sheet = file
  ocrError.value = ''
  ocrText.value = ''
  ocrConfidence.value = null
}

function chooseResultSheet(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  input.value = ''
  if (!file) return
  if (!file.type.startsWith('image/')) {
    ocrError.value = 'Please choose a photo of the result sheet.'
    return
  }
  if (file.size > 10 * 1024 * 1024) {
    ocrError.value = 'The photo is too large. Please choose an image smaller than 10 MB.'
    return
  }
  setResultSheet(file)
}

function loadOcrEngine(): Promise<void> {
  if (window.Tesseract) return Promise.resolve()
  if (!tesseractScript) {
    const loading = new Promise<void>((resolve, reject) => {
      const script = document.createElement('script')
      script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js'
      script.async = true
      script.crossOrigin = 'anonymous'
      script.onload = () => window.Tesseract ? resolve() : reject(new Error('The text reader could not be loaded.'))
      script.onerror = () => reject(new Error('Could not load the text reader. Check your internet connection and try again.'))
      document.head.appendChild(script)
    })
    tesseractScript = loading.catch((cause) => {
      tesseractScript = null
      throw cause
    })
  }
  return tesseractScript
}

function extractNumber(text: string, expression: RegExp): number | null {
  const match = text.match(expression)
  if (!match?.[1]) return null
  const value = Number(match[1].replace(/,/g, ''))
  return Number.isSafeInteger(value) && value >= 0 ? value : null
}

function extractPartyNumber(lines: string[], acronym: string): number | null {
  const escaped = acronym.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const expression = new RegExp(`^\\s*${escaped}\\s*(?:[:|.-]\\s*)?([0-9][0-9,]{0,10})\\b`, 'i')
  for (let index = 0; index < lines.length; index++) {
    const match = lines[index].match(expression)
    if (match?.[1]) {
      const value = Number(match[1].replace(/,/g, ''))
      if (Number.isSafeInteger(value) && value >= 0) return value
    }
    if (new RegExp(`^\\s*${escaped}\\s*$`, 'i').test(lines[index]) && lines[index + 1]) {
      const next = lines[index + 1].match(/^\s*([0-9][0-9,]{0,10})\b/)
      if (next?.[1]) return Number(next[1].replace(/,/g, ''))
    }
  }
  return null
}

async function readFiguresFromPhoto() {
  const imageFile = resultSheetFile.value
  if (!imageFile || ocrBusy.value || !canEdit) return
  ocrBusy.value = true
  ocrProgress.value = 0
  ocrError.value = ''
  let worker: OcrWorker | null = null

  try {
    await loadOcrEngine()
    worker = await window.Tesseract!.createWorker('eng', 1, {
      logger: (message) => {
        if (message.status === 'recognizing text' && typeof message.progress === 'number') {
          ocrProgress.value = Math.round(message.progress * 100)
        }
      },
    })
    const { data } = await worker.recognize(imageFile)
    ocrText.value = data.text
    ocrConfidence.value = Math.round(data.confidence)

    const lines = data.text.split(/\r?\n/).map((line) => line.trim()).filter(Boolean)
    const accredited = extractNumber(data.text, /\b(?:accredited\s+voters|accredited)\b[^0-9]{0,30}([0-9][0-9,]{0,10})/i)
    const rejected = extractNumber(data.text, /\b(?:rejected\s+votes|rejected)\b[^0-9]{0,30}([0-9][0-9,]{0,10})/i)
    if (accredited !== null) form.accredited_voters = accredited
    if (rejected !== null) form.rejected_votes = rejected

    const nextVotes = { ...form.party_votes }
    let matched = Number(accredited !== null) + Number(rejected !== null)
    for (const party of props.parties) {
      const votes = extractPartyNumber(lines, party.value)
      if (votes !== null) {
        nextVotes[party.value] = votes
        matched++
      }
    }
    form.party_votes = nextVotes

    if (matched === 0) {
      ocrError.value = 'The reader could not identify any vote figures. Enter the numbers manually and check the photo.'
    } else {
      ocrError.value = `Read ${matched} figure(s). Please compare every number with the original result sheet before submitting.`
    }
  } catch (cause) {
    ocrError.value = cause instanceof Error ? cause.message : 'The result sheet could not be read. Please enter the figures manually.'
  } finally {
    await worker?.terminate().catch(() => undefined)
    ocrBusy.value = false
  }
}

onBeforeUnmount(() => {
  if (resultSheetPreview.value) URL.revokeObjectURL(resultSheetPreview.value)
})
</script>

<template>
  <Head :title="`Party figures · ${pu.name}`" />
  <div class="mx-auto w-full max-w-3xl space-y-5">
    <Link :href="route('portal.pus.show', { pu: pu.id, office })" class="inline-flex items-center gap-2 text-sm font-bold text-red-700 hover:underline">
      <ArrowLeft class="h-4 w-4" /> Back to polling unit tasks
    </Link>

    <section class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
      <div class="flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-700"><Vote class="h-5 w-5" /></div>
        <div class="min-w-0">
          <h1 class="text-lg font-black text-neutral-900">Enter party figures</h1>
          <p class="mt-1 text-sm text-neutral-600">{{ pu.name }} <span v-if="pu.code">· {{ pu.code }}</span></p>
          <p class="text-xs text-neutral-500">{{ pu.ward ?? 'Ward not set' }} Ward · {{ pu.lga ?? 'LGA not set' }} LGA</p>
        </div>
      </div>
    </section>

    <section v-if="!elections.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
      <h2 class="font-black text-amber-900">No active election is available</h2>
      <p class="mt-1 text-sm text-amber-800">Please contact the administrator. They need to create and activate an election before party figures can be submitted.</p>
    </section>

    <form v-else class="space-y-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block text-sm font-semibold text-neutral-700">Election
          <select v-model="electionId" class="mt-1.5 min-h-11 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm" :disabled="elections.length < 2">
            <option v-for="item in elections" :key="item.id" :value="item.id">{{ item.name }}{{ item.date ? ` · ${item.date}` : '' }}</option>
          </select>
        </label>
        <label class="block text-sm font-semibold text-neutral-700">Office
          <select v-model="office" class="mt-1.5 min-h-11 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm" :disabled="offices.length < 2">
            <option v-for="item in offices" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
        </label>
      </div>

      <div class="rounded-xl bg-neutral-50 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-neutral-500">Registered voters</p>
        <p class="mt-1 text-2xl font-black tabular-nums text-neutral-900">{{ pu.registered_voters.toLocaleString() }}</p>
        <p class="mt-1 text-xs text-neutral-500">Read-only figure from the polling-unit record.</p>
      </div>

      <div v-if="result" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold" :class="result.status === 'verified' ? 'bg-green-50 text-green-800' : result.status === 'disputed' ? 'bg-red-50 text-red-800' : 'bg-amber-50 text-amber-800'">
        <CheckCircle2 v-if="result.status === 'verified'" class="h-4 w-4" />
        <LockKeyhole v-else-if="!canEdit" class="h-4 w-4" />
        {{ statusLabel }} result
        <span v-if="result.updated_at" class="ml-auto text-xs font-normal">{{ new Date(result.updated_at).toLocaleString() }}</span>
      </div>
      <p v-if="!canEdit" class="rounded-lg bg-neutral-100 p-3 text-sm text-neutral-600">This submission can no longer be changed because it has been verified or disputed. Contact an administrator if a correction is required.</p>

      <section v-if="canEdit" class="space-y-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
        <div class="flex items-center gap-2">
          <ScanText class="h-5 w-5 text-red-600" />
          <div>
            <h2 class="text-sm font-black text-neutral-900">Read figures from result-sheet photo</h2>
            <p class="text-xs text-neutral-500">Take a new photo or choose one already on your device.</p>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-red-600 px-3 text-sm font-bold text-white hover:bg-red-700" @click="cameraInput?.click()">
            <Camera class="h-4 w-4" />Take photo
          </button>
          <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-3 text-sm font-bold text-neutral-800 hover:bg-neutral-100" @click="galleryInput?.click()">
            <ImagePlus class="h-4 w-4" />Choose from device
          </button>
        </div>
        <input ref="cameraInput" type="file" accept="image/*" capture="environment" class="sr-only" @change="chooseResultSheet" />
        <input ref="galleryInput" type="file" accept="image/*" class="sr-only" @change="chooseResultSheet" />
        <div v-if="resultSheetPreview" class="flex flex-col gap-3 sm:flex-row sm:items-start">
          <img :src="resultSheetPreview" alt="Selected result sheet" class="max-h-72 w-full rounded-lg border border-neutral-200 bg-white object-contain sm:max-w-xs" />
          <div class="flex-1 space-y-2">
            <p class="break-all text-xs font-semibold text-neutral-700">{{ resultSheetFile?.name }}</p>
            <button type="button" :disabled="ocrBusy" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg bg-neutral-900 px-3 text-sm font-bold text-white hover:bg-neutral-800 disabled:opacity-50" @click="readFiguresFromPhoto">
              <ScanText class="h-4 w-4" />{{ ocrBusy ? `Reading photo… ${ocrProgress}%` : 'Read figures from photo' }}
            </button>
            <button type="button" class="inline-flex min-h-9 items-center gap-1 text-xs font-bold text-red-700 hover:underline" @click="setResultSheet(null)">
              <Trash2 class="h-3.5 w-3.5" />Remove photo
            </button>
          </div>
        </div>
        <p v-if="ocrError" class="text-xs font-semibold" :class="ocrError.startsWith('Read ') ? 'text-amber-800' : 'text-red-700'" role="status">{{ ocrError }}</p>
        <p v-if="ocrConfidence !== null" class="text-xs text-neutral-600">Text recognition confidence: {{ ocrConfidence }}%. Handwritten figures may be read incorrectly.</p>
        <details v-if="ocrText" class="rounded-lg border border-neutral-200 bg-white p-3">
          <summary class="cursor-pointer text-xs font-bold text-neutral-700">Show text detected in the photo</summary>
          <pre class="mt-2 max-h-48 overflow-auto whitespace-pre-wrap text-xs text-neutral-600">{{ ocrText }}</pre>
        </details>
        <p class="text-[11px] leading-relaxed text-neutral-500">The photo is attached to your submission as evidence. Text recognition runs in your browser and needs an internet connection the first time. Always check the numbers yourself before submitting.</p>
        <p v-if="form.errors.result_sheet" class="text-xs font-semibold text-red-700">{{ form.errors.result_sheet }}</p>
      </section>

      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block text-sm font-semibold text-neutral-700">Accredited voters
          <input v-model.number="form.accredited_voters" type="number" min="0" class="mt-1.5 min-h-11 w-full rounded-lg border border-neutral-300 px-3 tabular-nums" :disabled="!canEdit" />
          <span v-if="form.errors.accredited_voters" class="mt-1 block text-xs text-red-600">{{ form.errors.accredited_voters }}</span>
        </label>
        <label class="block text-sm font-semibold text-neutral-700">Rejected votes
          <input v-model.number="form.rejected_votes" type="number" min="0" class="mt-1.5 min-h-11 w-full rounded-lg border border-neutral-300 px-3 tabular-nums" :disabled="!canEdit" />
          <span v-if="form.errors.rejected_votes" class="mt-1 block text-xs text-red-600">{{ form.errors.rejected_votes }}</span>
        </label>
      </div>

      <fieldset class="space-y-3">
        <legend class="text-sm font-black text-neutral-900">Votes recorded for each party</legend>
        <p class="text-xs text-neutral-500">Enter the figures exactly as shown on the polling-unit result sheet. Use zero where a party received no votes.</p>
        <div class="grid gap-3 sm:grid-cols-2">
          <label v-for="party in parties" :key="party.value" class="block text-sm font-semibold text-neutral-700">{{ party.label }} votes
            <input v-model.number="form.party_votes[party.value]" type="number" min="0" class="mt-1.5 min-h-11 w-full rounded-lg border border-neutral-300 px-3 tabular-nums" :disabled="!canEdit" />
          </label>
        </div>
        <p v-if="form.errors.party_votes" class="text-xs font-medium text-red-600">{{ form.errors.party_votes }}</p>
      </fieldset>

      <div class="grid grid-cols-2 gap-3 rounded-xl bg-neutral-50 p-4 text-sm">
        <div><p class="text-xs text-neutral-500">Total valid votes</p><p class="mt-1 text-xl font-black tabular-nums text-neutral-900">{{ validVotes.toLocaleString() }}</p></div>
        <div><p class="text-xs text-neutral-500">Valid + rejected</p><p class="mt-1 text-xl font-black tabular-nums" :class="totalVotes > form.accredited_voters ? 'text-red-700' : 'text-neutral-900'">{{ totalVotes.toLocaleString() }}</p></div>
      </div>

      <label class="block text-sm font-semibold text-neutral-700">Remarks (optional)
        <textarea v-model="form.remarks" rows="3" class="mt-1.5 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm" placeholder="Add a note for the result reviewer…" :disabled="!canEdit" />
        <span v-if="form.errors.remarks" class="mt-1 block text-xs text-red-600">{{ form.errors.remarks }}</span>
      </label>

      <div class="flex flex-col-reverse gap-2 border-t border-neutral-100 pt-4 sm:flex-row sm:justify-end">
        <Link :href="route('portal.pus.show', { pu: pu.id, office })" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-neutral-300 px-4 text-sm font-bold text-neutral-700">Cancel</Link>
        <button v-if="canEdit" type="submit" :disabled="form.processing || totalVotes > form.accredited_voters" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-red-600 px-5 text-sm font-black text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
          {{ form.processing ? 'Submitting…' : result ? 'Update party figures' : 'Submit party figures' }}
        </button>
      </div>
    </form>
  </div>
</template>
