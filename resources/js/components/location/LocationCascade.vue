<script setup lang="ts">
import { nextTick, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import { useJurisdiction } from '@/composables/useJurisdiction'
import type { LocationKey } from '@/types/auth'

interface Opt { id: string; name: string }
type Model = Partial<Record<`${LocationKey}_id`, string | null>>

const props = withDefaults(defineProps<{
  modelValue: Model
  upTo?: LocationKey
  errors?: Record<string, string>
  disabled?: boolean
}>(), { upTo: 'ward', errors: () => ({}), disabled: false })

const emit = defineEmits<{ 'update:modelValue': [v: Model] }>()
const { isLocked, lockedValue } = useJurisdiction()

const LEVELS: { key: LocationKey; label: string; route: string; parentParam?: string }[] = [
  { key: 'state', label: 'State', route: 'location-options.states' },
  { key: 'zone', label: 'Zone', route: 'location-options.zones', parentParam: 'state_id' },
  { key: 'lga', label: 'LGA', route: 'location-options.lgas', parentParam: 'zone_id' },
  { key: 'ward', label: 'Ward', route: 'location-options.wards', parentParam: 'lga_id' },
  { key: 'pu', label: 'Polling unit', route: 'location-options.pus', parentParam: 'ward_id' },
]
const visible = LEVELS.slice(0, LEVELS.findIndex((l) => l.key === props.upTo) + 1)

const options = reactive<Record<string, Opt[]>>({})
const loading = ref<Record<string, boolean>>({})
const value = (k: LocationKey) => props.modelValue[`${k}_id`] ?? null

async function load(index: number) {
  const level = LEVELS[index]
  const parent = level.parentParam ? props.modelValue[level.parentParam as keyof Model] : null
  if (level.parentParam && !parent) { options[level.key] = []; return }

  loading.value[level.key] = true
  try {
    const { data } = await axios.get(route(level.route), {
      params: level.parentParam ? { [level.parentParam]: parent } : {},
    })
    options[level.key] = data
  } finally {
    loading.value[level.key] = false
  }
}

async function change(index: number, id: string | null) {
  const next: Model = { ...props.modelValue, [`${LEVELS[index].key}_id`]: id }
  for (const l of LEVELS.slice(index + 1)) { next[`${l.key}_id`] = null; options[l.key] = [] }
  emit('update:modelValue', next)

  if (index + 1 < visible.length) {
    await nextTick() // parent has applied the new value by now
    load(index + 1)
  }
}

onMounted(async () => {
  // Fix the levels this user is locked to, then hydrate every level that already has a value.
  const seeded: Model = { ...props.modelValue }
  let changed = false
  for (const l of LEVELS) {
    if (isLocked(l.key) && seeded[`${l.key}_id`] !== lockedValue(l.key)) {
      seeded[`${l.key}_id`] = lockedValue(l.key)
      changed = true
    }
  }
  if (changed) { emit('update:modelValue', seeded); await nextTick() }

  for (let i = 0; i < visible.length; i++) {
    if (i === 0 || seeded[`${LEVELS[i - 1].key}_id`]) await load(i)
  }
})
</script>

<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div v-for="(level, i) in visible" :key="level.key" :class="level.key === 'pu' ? 'sm:col-span-2' : ''">
      <label class="mb-1 block text-xs font-medium text-gray-600">{{ level.label }}</label>
      <select
        :key="`${level.key}-${options[level.key]?.length ?? 0}`"
        :value="value(level.key) ?? ''"
        :disabled="disabled || isLocked(level.key) || (i > 0 && !value(LEVELS[i - 1].key)) || loading[level.key]"
        class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 disabled:bg-gray-50 disabled:text-gray-400"
        @change="change(i, ($event.target as HTMLSelectElement).value || null)"
      >
        <option value="">{{ loading[level.key] ? 'Loading…' : `Select ${level.label.toLowerCase()}` }}</option>
        <option v-for="o in options[level.key]" :key="o.id" :value="o.id">{{ o.name }}</option>
      </select>
      <p v-if="errors[`${level.key}_id`]" class="mt-1 text-xs text-red-600">{{ errors[`${level.key}_id`] }}</p>
    </div>
  </div>
</template>