<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Save, ShieldHalf, UserRound } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/form/Input.vue'
import PuHeader from '@/components/pu/PuHeader.vue'

const props = defineProps<{
  pu: { id: string; name: string; code: string; ward: string | null; lga: string | null }
  official: { po_name: string; apo1_name: string; apo2_name: string; security_name: string } | null
  updatedAt: string | null
  office: string
}>()

const form = useForm({
  po_name: props.official?.po_name ?? '',
  apo1_name: props.official?.apo1_name ?? '',
  apo2_name: props.official?.apo2_name ?? '',
  security_name: props.official?.security_name ?? '',
})

const submit = () => form.put(route('portal.pus.officials.save', { pu: props.pu.id, office: props.office }), { preserveScroll: true })
</script>

<template>
  <Head title="Polling unit officials" />

  <PuHeader :pu="pu" :office="office" title="Officials" subtitle="Write the full name of each person on duty at this polling unit." />

  <form class="space-y-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
    <div class="space-y-4">
      <Input v-model="form.po_name" label="PO (Presiding Officer)" placeholder="Full name" :icon="UserRound" :error="form.errors.po_name" required autocomplete="off" />
      <Input v-model="form.apo1_name" label="APO 1" placeholder="Full name" :icon="UserRound" :error="form.errors.apo1_name" required autocomplete="off" />
      <Input v-model="form.apo2_name" label="APO 2" placeholder="Full name" :icon="UserRound" :error="form.errors.apo2_name" required autocomplete="off" />
      <Input v-model="form.security_name" label="Security person" placeholder="Full name" :icon="ShieldHalf" :error="form.errors.security_name" required autocomplete="off" />
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <Button variant="neutral" :href="route('portal.pus.show', { pu: pu.id, office })" class="sm:w-auto" block>Cancel</Button>
      <Button type="submit" :loading="form.processing" block class="sm:w-auto">
        <Save class="h-4 w-4" /> {{ official ? 'Save changes' : 'Save names' }}
      </Button>
    </div>
  </form>
</template>
