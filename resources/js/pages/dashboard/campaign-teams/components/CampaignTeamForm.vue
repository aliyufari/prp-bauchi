<script setup lang="ts">
import type { AspirantOption, UserOption } from '@/types/campaign-team'

interface FormShape {
  aspirant_id: string | null
  name: string
  description: string | null
  team_lead_id: string | null
  is_active: boolean
  errors: Record<string, string>
  processing: boolean
}

interface Props {
  form: FormShape
  submitLabel?: string
  aspirants?: AspirantOption[]
  potentialLeads?: UserOption[]
}
withDefaults(defineProps<Props>(), { submitLabel: 'Save', aspirants: () => [], potentialLeads: () => [] })
const emit = defineEmits<{ submit: []; cancel: [] }>()
</script>

<template>
  <form @submit.prevent="emit('submit')" class="space-y-6">
    <fieldset class="space-y-4">
      <div v-if="'aspirant_id' in form">
        <label class="mb-1 block text-xs font-medium text-gray-600">Campaign (Aspirant)</label>
        <select v-model="form.aspirant_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
          <option value="" disabled>Select aspirant</option>
          <option v-for="a in aspirants" :key="a.id" :value="a.id">{{ a.name }}{{ a.office ? ` (${a.office})` : '' }}</option>
        </select>
        <p v-if="form.errors.aspirant_id" class="mt-1 text-xs text-red-600">{{ form.errors.aspirant_id }}</p>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Team name</label>
        <input v-model="form.name" type="text" placeholder="e.g. Ground Operations Team" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Description</label>
        <textarea v-model="form.description" rows="2" placeholder="Description" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Team lead</label>
        <select v-model="form.team_lead_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500">
          <option :value="null">Unassigned</option>
          <option v-for="u in potentialLeads" :key="u.id" :value="u.id">{{ u.name }}</option>
        </select>
        <p class="mt-1 text-xs text-gray-400">The team lead can add/remove their own team's members.</p>
      </div>

      <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600">
        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-green-700 focus:ring-green-500" />
        Team is active
      </label>
    </fieldset>

    <div class="flex items-center justify-end gap-3 pt-2">
      <button type="button" @click="emit('cancel')" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-500 transition hover:bg-gray-100">Cancel</button>
      <button type="submit" :disabled="form.processing" class="rounded-xl bg-green-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 active:bg-green-900 disabled:cursor-not-allowed disabled:opacity-60">
        {{ form.processing ? 'Saving…' : submitLabel }}
      </button>
    </div>
  </form>
</template>