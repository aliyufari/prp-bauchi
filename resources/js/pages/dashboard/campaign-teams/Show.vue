<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Plus, UserCog } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import StatusBadge from '@/components/tables/StatusBadge.vue'
import ActionDropdown from '@/components/tables/ActionDropdown.vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import type { CampaignTeam, CampaignTeamMember } from '@/types/campaign-team'

interface Props {
  team: CampaignTeam
  members: CampaignTeamMember[]
}
const props = defineProps<Props>()

const modalOpen = ref(false)
const editingMember = ref<CampaignTeamMember | null>(null)

function emptyForm() {
  return {
    first_name: '', last_name: '', email: '', password: '', phone: '' as string | null,
    role_in_team: '', joined_date: '' as string | null, is_active: true,
  }
}
const form = useForm(emptyForm())

function openAdd() {
  editingMember.value = null
  form.clearErrors(); form.defaults(emptyForm()); form.reset()
  modalOpen.value = true
}

function openEdit(member: CampaignTeamMember) {
  editingMember.value = member
  form.clearErrors()
  const values = { ...emptyForm(), role_in_team: member.role_in_team, joined_date: member.joined_date, is_active: member.status === 'active' }
  form.defaults(values); form.reset(); Object.assign(form, values)
  modalOpen.value = true
}

function closeModal() { modalOpen.value = false; editingMember.value = null; form.clearErrors() }

function submit() {
  const options = { preserveScroll: true, onSuccess: () => closeModal() }
  if (editingMember.value) {
    form.patch(route('campaign-teams.members.update', [props.team.id, editingMember.value.id]), options)
  } else {
    form.post(route('campaign-teams.members.store', props.team.id), options)
  }
}

function removeMember(member: CampaignTeamMember) {
  if (confirm(`Remove ${member.first_name} ${member.last_name} from ${props.team.name}?`)) {
    router.delete(route('campaign-teams.members.destroy', [props.team.id, member.id]), { preserveScroll: true })
  }
}
</script>

<template>
  <Head :title="team.name" />
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h2 class="text-lg font-black text-gray-900 sm:text-xl">{{ team.name }}</h2>
        <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">
          {{ team.aspirant_name }}{{ team.aspirant_office ? ` — ${team.aspirant_office}` : '' }} ·
          Led by {{ team.team_lead_name ?? 'Unassigned' }}
        </p>
      </div>

      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-700">Roster ({{ members.length }})</h3>
        <button
          v-if="team.can_manage"
          type="button"
          @click="openAdd"
          class="inline-flex items-center gap-2 rounded-xl bg-green-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-green-800"
        >
          <Plus class="h-4 w-4" />
          Add Member
        </button>
      </div>

      <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <div v-if="members.length === 0" class="p-8 text-center text-sm text-gray-400">
          No members on this team yet.
        </div>
        <div v-for="member in members" :key="member.id" class="flex items-center justify-between border-b border-gray-50 p-4 last:border-0">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-700">
              <UserCog class="h-4 w-4" />
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900">{{ member.first_name }} {{ member.last_name }}</p>
              <p class="text-xs text-gray-400">{{ member.role_in_team }} · {{ member.email }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <StatusBadge :status="member.status" />
            <ActionDropdown
              v-if="team.can_manage"
              :actions="[
                { key: 'edit', label: 'Edit', icon: 'edit' },
                { key: 'delete', label: 'Remove', icon: 'delete', danger: true },
              ]"
              @action="(key) => key === 'edit' ? openEdit(member) : removeMember(member)"
            />
          </div>
        </div>
      </div>
    </div>

    <SlideOver
      :open="modalOpen"
      :title="editingMember ? 'Edit Member' : 'Add Member'"
      size="md" side="right" @close="closeModal"
    >
      <form @submit.prevent="submit" class="space-y-4">
        <template v-if="!editingMember">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600">First name</label>
              <input v-model="form.first_name" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
              <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-600">{{ form.errors.first_name }}</p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600">Last name</label>
              <input v-model="form.last_name" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
              <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-600">{{ form.errors.last_name }}</p>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Email (used for login)</label>
            <input v-model="form.email" type="email" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Password</label>
            <input v-model="form.password" type="password" autocomplete="new-password" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600">Phone</label>
            <input v-model="form.phone" type="tel" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          </div>
        </template>

        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Role on team</label>
          <input v-model="form.role_in_team" type="text" placeholder="e.g. Field Officer" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          <p v-if="form.errors.role_in_team" class="mt-1 text-xs text-red-600">{{ form.errors.role_in_team }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-600">Joined date</label>
          <input v-model="form.joined_date" type="date" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
        </div>
        <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-green-700 focus:ring-green-500" />
          Active on the team
        </label>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" @click="closeModal" class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-500 transition hover:bg-gray-100">Cancel</button>
          <button type="submit" :disabled="form.processing" class="rounded-xl bg-green-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 disabled:opacity-60">
            {{ form.processing ? 'Saving…' : editingMember ? 'Save changes' : 'Add member' }}
          </button>
        </div>
      </form>
    </SlideOver>
  </AppLayout>
</template>