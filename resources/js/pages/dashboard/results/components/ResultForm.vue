<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import type { LocationOption, OfficeOption, PartyOption } from '@/types/result'

interface FormShape {
  state_id: string | null
  zone_id: string | null
  lga_id: string | null
  ward_id: string | null
  pu_id: string | null
  office: string | null
  election_date: string | null
  registered_voters: number
  accredited_voters: number
  rejected_votes: number
  party_votes: Record<string, number>
  result_sheet_url: string | null
  remarks: string | null
  errors: Record<string, string>
  processing: boolean
}

interface Props {
  form: FormShape
  submitLabel?: string
  offices?: OfficeOption[]
  parties?: PartyOption[]
  states?: LocationOption[]
}
const props = withDefaults(defineProps<Props>(), {
  submitLabel: 'Save',
  offices: () => [],
  parties: () => [],
  states: () => [],
})

const emit = defineEmits<{ submit: []; cancel: [] }>()

const zones = ref<LocationOption[]>([])
const lgas = ref<LocationOption[]>([])
const wards = ref<LocationOption[]>([])
const pus = ref<(LocationOption & { code?: string; registered_voters?: number })[]>([])
const loading = ref({ zones: false, lgas: false, wards: false, pus: false })

async function loadZones(stateId: string | null) {
  zones.value = []
  if (!stateId) return
  loading.value.zones = true
  try {
    const { data } = await axios.get(route('location-options.zones'), { params: { state_id: stateId } })
    zones.value = data
  } finally {
    loading.value.zones = false
  }
}

async function loadLgas(zoneId: string | null) {
  lgas.value = []
  if (!zoneId) return
  loading.value.lgas = true
  try {
    const { data } = await axios.get(route('location-options.lgas'), { params: { zone_id: zoneId } })
    lgas.value = data
  } finally {
    loading.value.lgas = false
  }
}

async function loadWards(lgaId: string | null) {
  wards.value = []
  if (!lgaId) return
  loading.value.wards = true
  try {
    const { data } = await axios.get(route('location-options.wards'), { params: { lga_id: lgaId } })
    wards.value = data
  } finally {
    loading.value.wards = false
  }
}

async function loadPus(wardId: string | null) {
  pus.value = []
  if (!wardId) return
  loading.value.pus = true
  try {
    const { data } = await axios.get(route('location-options.pus'), { params: { ward_id: wardId } })
    pus.value = data
  } finally {
    loading.value.pus = false
  }
}

watch(() => props.form.state_id, (val) => {
  props.form.zone_id = null; props.form.lga_id = null; props.form.ward_id = null; props.form.pu_id = null
  loadZones(val)
})
watch(() => props.form.zone_id, (val) => {
  props.form.lga_id = null; props.form.ward_id = null; props.form.pu_id = null
  loadLgas(val)
})
watch(() => props.form.lga_id, (val) => {
  props.form.ward_id = null; props.form.pu_id = null
  loadWards(val)
})
watch(() => props.form.ward_id, (val) => {
  props.form.pu_id = null
  loadPus(val)
})
watch(() => props.form.pu_id, (val) => {
  const selected = pus.value.find((p) => p.id === val)
  if (selected?.registered_voters) {
    props.form.registered_voters = selected.registered_voters
  }
})

// Pre-hydrate dependent lists if the form opens with values already set
// (e.g. re-opening after a failed submit).
if (props.form.state_id) loadZones(props.form.state_id)
if (props.form.zone_id) loadLgas(props.form.zone_id)
if (props.form.lga_id) loadWards(props.form.lga_id)
if (props.form.ward_id) loadPus(props.form.ward_id)

const totalValidVotes = computed(() =>
  Object.values(props.form.party_votes || {}).reduce((sum, v) => sum + (Number(v) || 0), 0)
)
</script>

<template>
  <form @submit.prevent="emit('submit')" class="space-y-6">
    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">Polling unit location</legend>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">State</label>
          <select v-model="form.state_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option :value="null" disabled>Select state</option>
            <option v-for="s in states" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Zone</label>
          <select v-model="form.zone_id" :disabled="!form.state_id || loading.zones" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 disabled:bg-gray-50 disabled:text-gray-400">
            <option :value="null" disabled>{{ loading.zones ? 'Loading…' : 'Select zone' }}</option>
            <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">LGA</label>
          <select v-model="form.lga_id" :disabled="!form.zone_id || loading.lgas" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 disabled:bg-gray-50 disabled:text-gray-400">
            <option :value="null" disabled>{{ loading.lgas ? 'Loading…' : 'Select LGA' }}</option>
            <option v-for="l in lgas" :key="l.id" :value="l.id">{{ l.name }}</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Ward</label>
          <select v-model="form.ward_id" :disabled="!form.lga_id || loading.wards" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 disabled:bg-gray-50 disabled:text-gray-400">
            <option :value="null" disabled>{{ loading.wards ? 'Loading…' : 'Select ward' }}</option>
            <option v-for="w in wards" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <label class="mb-1 block text-xs font-medium text-gray-600">Polling unit</label>
          <select v-model="form.pu_id" :disabled="!form.ward_id || loading.pus" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 disabled:bg-gray-50 disabled:text-gray-400">
            <option :value="null" disabled>{{ loading.pus ? 'Loading…' : 'Select polling unit' }}</option>
            <option v-for="p in pus" :key="p.id" :value="p.id">{{ p.name }}{{ p.code ? ` (${p.code})` : '' }}</option>
          </select>
          <p v-if="form.errors.pu_id" class="mt-1 text-xs text-red-600">{{ form.errors.pu_id }}</p>
        </div>
      </div>
    </fieldset>

    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">Election details</legend>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Office</label>
          <select v-model="form.office" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="" disabled>Select office</option>
            <option v-for="o in offices" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
          <p v-if="form.errors.office" class="mt-1 text-xs text-red-600">{{ form.errors.office }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Election date</label>
          <input v-model="form.election_date" type="date" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Registered voters</label>
          <input v-model.number="form.registered_voters" type="number" min="0" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Accredited voters</label>
          <input v-model.number="form.accredited_voters" type="number" min="0" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          <p v-if="form.errors.accredited_voters" class="mt-1 text-xs text-red-600">{{ form.errors.accredited_voters }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Rejected votes</label>
          <input v-model.number="form.rejected_votes" type="number" min="0" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
        </div>
      </div>
    </fieldset>

    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">
        Votes by party
        <span class="ml-2 font-normal normal-case text-gray-400">Total valid: {{ totalValidVotes.toLocaleString() }}</span>
      </legend>

      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <div v-for="party in parties" :key="party.value">
          <label class="mb-1 block text-xs font-medium text-gray-600">{{ party.label }}</label>
          <input
            v-model.number="form.party_votes[party.value]"
            type="number"
            min="0"
            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
          />
        </div>
      </div>
      <p v-if="form.errors.party_votes" class="text-xs text-red-600">{{ form.errors.party_votes }}</p>
    </fieldset>

    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">Supporting information</legend>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Result sheet URL</label>
        <input v-model="form.result_sheet_url" type="text" placeholder="Link to scanned result sheet (optional)" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Remarks</label>
        <textarea v-model="form.remarks" rows="2" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
      </div>
    </fieldset>

    <div class="flex items-center justify-end gap-3 pt-2">
      <button type="button" @click="emit('cancel')" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-500 transition hover:bg-gray-100">Cancel</button>
      <button type="submit" :disabled="form.processing" class="rounded-xl bg-green-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 disabled:opacity-60">
        {{ form.processing ? 'Saving…' : submitLabel }}
      </button>
    </div>
  </form>
</template>