<script setup lang="ts">
import type { WardOption } from '@/types/polling-unit'

interface FormShape {
  name: string
  code: string | null
  ward_id: string | null
  address: string | null
  registered_voters: number

  errors: Record<string, string>
  processing: boolean
}

interface Props {
  form: FormShape
  submitLabel?: string
  wards?: WardOption[]
}
withDefaults(defineProps<Props>(), {
  submitLabel: 'Save',
  wards: () => [],
})

const emit = defineEmits<{ submit: []; cancel: [] }>()
</script>

<template>
  <form @submit.prevent="emit('submit')" class="space-y-6">
    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">Polling unit details</legend>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Name</label>
        <input
          v-model="form.name"
          type="text"
          placeholder="e.g. Ward 1 Primary School Hall A"
          class="w-full rounded-xl border border-gray-200 text-gray-700 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
        />
        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Code</label>
          <input
            v-model="form.code"
            type="text"
            placeholder="e.g. PU/001/BA"
            class="w-full rounded-xl border border-gray-200 text-gray-700 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
          />
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Ward</label>
          <select
            v-model="form.ward_id"
            class="w-full rounded-xl border border-gray-200 text-gray-700 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            <option :value="null" disabled>Select ward</option>
            <option v-for="w in wards" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
        </div>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Address</label>
        <input
          v-model="form.address"
          type="text"
          class="w-full rounded-xl border border-gray-200 text-gray-700 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
        />
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Registered voters</label>
        <input
          v-model.number="form.registered_voters"
          type="number"
          min="0"
          class="w-full rounded-xl border border-gray-200 text-gray-700 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500"
        />
        <p v-if="form.errors.registered_voters" class="mt-1 text-xs text-red-600">{{ form.errors.registered_voters }}</p>
      </div>
    </fieldset>

    <div class="flex items-center justify-end gap-3 pt-2">
      <button
        type="button"
        @click="emit('cancel')"
        class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-500 transition hover:bg-gray-100"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="form.processing"
        class="rounded-xl bg-green-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 active:bg-green-900 disabled:cursor-not-allowed disabled:opacity-60"
      >
        {{ form.processing ? 'Saving…' : submitLabel }}
      </button>
    </div>
  </form>
</template>