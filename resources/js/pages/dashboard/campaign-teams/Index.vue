<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import { useToast } from '@/composables/useToast'
import ConfirmDialog from '@/components/ui/overlay/ConfirmDialog.vue'
import { ref, computed, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Plus, Users2 } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import DataTable from '@/components/tables/DataTable.vue'
import DataTableToolbar from '@/components/tables/DataTableToolbar.vue'
import DataTableFilters, { type FilterDef } from '@/components/tables/DataTableFilters.vue'
import DataTablePagination from '@/components/tables/DataTablePagination.vue'
import StatusBadge from '@/components/tables/StatusBadge.vue'
import ActionDropdown from '@/components/tables/ActionDropdown.vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import StatCard from '@/components/dashboard/StatCard.vue'
import CampaignTeamForm from './components/CampaignTeamForm.vue'
import { columns } from './columns'
import { useDataTable } from '@/composables/table/useDataTable'
import type { CampaignTeam, CampaignTeamStatistics, CampaignTeamFilterOptions, AspirantOption, UserOption } from '@/types/campaign-team'
import type { DataTableResponse } from '@/types/table'

interface FormOptions {
  aspirants: AspirantOption[]
  potentialLeads: UserOption[]
}

interface Props {
  teams: DataTableResponse<CampaignTeam>
  statistics: CampaignTeamStatistics
  filterOptions: CampaignTeamFilterOptions
  formOptions: FormOptions
  filters: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  filters: () => ({}),
  filterOptions: () => ({ aspirants: [], statuses: [] }),
  formOptions: () => ({ aspirants: [], potentialLeads: [] }),
})

const table = useDataTable()
const toast = useToast()
table.search.value = props.filters?.search ?? ''
table.filters.value = { aspirant: props.filters?.aspirant ?? '', status: props.filters?.status ?? '' }

const filterDefs = computed<FilterDef[]>(() => [
  { key: 'aspirant', label: 'Campaign', options: props.filterOptions.aspirants.map((a) => ({ value: a.id, label: a.name })) },
  { key: 'status', label: 'Status', options: props.filterOptions.statuses },
])

function query() {
  router.get(route('campaign-teams.index'), {
    search: table.search.value,
    ...table.filters.value,
    sort_by: table.sortBy.value,
    sort_direction: table.sortDirection.value,
    page: table.page.value,
    per_page: table.perPage.value,
  }, { preserveState: true, preserveScroll: true, replace: true })
}

let debounce: ReturnType<typeof setTimeout>
watch(table.search, () => { clearTimeout(debounce); table.page.value = 1; debounce = setTimeout(query, 350) })
watch(table.filters, () => { table.page.value = 1; query() }, { deep: true })
watch([table.sortBy, table.sortDirection, table.page, table.perPage], query)

/* ------------------------------------------------------------------ */
type ModalMode = 'create' | 'edit' | null
const modalMode = ref<ModalMode>(null)
const editingTeam = ref<CampaignTeam | null>(null)

function emptyForm() {
  return { aspirant_id: '' as string | null, name: '', description: '' as string | null, team_lead_id: null as string | null, is_active: true }
}
const form = useForm(emptyForm())

function openCreate() {
  editingTeam.value = null
  form.clearErrors(); form.defaults(emptyForm()); form.reset()
  modalMode.value = 'create'
}

function openEdit(row: CampaignTeam) {
  editingTeam.value = row
  form.clearErrors()
  const values = { aspirant_id: row.aspirant_id, name: row.name, description: row.description, team_lead_id: row.team_lead_id, is_active: row.status === 'active' }
  form.defaults(values); form.reset(); Object.assign(form, values)
  modalMode.value = 'edit'
}

function closeModal() { modalMode.value = null; editingTeam.value = null; form.clearErrors() }

function submitForm() {
  const options = { preserveScroll: true, onSuccess: () => { closeModal(); toast.success('Changes saved successfully.'); router.reload() } }
  if (modalMode.value === 'create') form.post(route('campaign-teams.store'), options)
  else if (modalMode.value === 'edit' && editingTeam.value) form.patch(route('campaign-teams.update', editingTeam.value.id), options)
}

function handleRowAction(key: string, row: CampaignTeam) {
  if (key === 'view') return router.visit(route('campaign-teams.show', row.id))
  if (key === 'edit') return openEdit(row)
  if (key === 'delete') {
    requestDelete(`Delete team "${row.name}"? This removes all roster entries too.`, () => {
    router.delete(route('campaign-teams.destroy', row.id), { preserveScroll: true, onSuccess: () => { toast.success('Record deleted.'); router.reload() } });
  
    })
  }
}

const deleteDialog = ref<{ message: string; action: () => void } | null>(null)
function requestDelete(message: string, action: () => void) {
  deleteDialog.value = { message, action }
}
function confirmDelete() {
  const pending = deleteDialog.value
  deleteDialog.value = null
  pending?.action()
}
function closeDeleteDialog() { deleteDialog.value = null }
</script>

<template>
  <Head title="Campaign Teams" />
  <AppLayout>
    <div class="space-y-4 sm:space-y-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
        <div class="min-w-0">
          <h2 class="text-lg font-black text-gray-900 sm:text-xl">Campaign Teams</h2>
          <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">Manage campaign teams and their assigned leads</p>
        </div>
        <Button @click="openCreate" class="w-full sm:w-auto">
          <Plus class="h-4 w-4" />
          Add Team
        </Button>
      </div>

      <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <StatCard :value="statistics.total" label="Total Teams" icon="Users2" icon-bg="bg-green-100" icon-color="text-green-700" />
        <StatCard :value="statistics.active" label="Active" icon="UserCheck" icon-bg="bg-green-50" icon-color="text-green-900" />
        <StatCard :value="statistics.total_members" label="Total Members" icon="Users" icon-bg="bg-gray-100" icon-color="text-gray-700" />
        <StatCard :value="statistics.unassigned_lead" label="No Team Lead" icon="AlertCircle" icon-bg="bg-amber-100" icon-color="text-amber-700" />
      </div>

      <DataTableToolbar v-model="table.search.value" placeholder="Search by team name..." show-refresh @refresh="query">
        <template #filters>
          <DataTableFilters :filters="filterDefs" v-model="table.filters.value" />
        </template>
      </DataTableToolbar>

      <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:overflow-visible sm:px-0">
        <div class="min-w-0">
          <DataTable :columns="columns" :rows="teams.data" :selectable="false" :sort-by="table.sortBy.value" :sort-direction="table.sortDirection.value" @sort="table.toggleSort">
            <template #team="{ row }">
              <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-700">
                  <Users2 class="h-4 w-4" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ row.name }}</p>
                  <p v-if="row.description" class="text-xs text-gray-400 line-clamp-1">{{ row.description }}</p>
                </div>
              </div>
            </template>
            <template #aspirant="{ row }">
              <div class="text-sm text-gray-700">{{ row.aspirant_name ?? '—' }}</div>
              <div v-if="row.aspirant_office" class="text-xs text-gray-400">{{ row.aspirant_office }}</div>
            </template>
            <template #lead="{ row }">
              <span class="text-sm text-gray-700">{{ row.team_lead_name ?? 'Unassigned' }}</span>
            </template>
            <template #members="{ row }">
              <span class="text-sm font-semibold text-gray-700">{{ row.member_count }}</span>
            </template>
            <template #status="{ row }"><StatusBadge :status="row.status" /></template>
            <template #actions="{ row }">
              <div class="flex justify-end">
                <ActionDropdown
                  :actions="[
                    { key: 'view', label: 'Manage roster', icon: 'eye' },
                    { key: 'edit', label: 'Edit team', icon: 'edit' },
                    { key: 'delete', label: 'Delete team', icon: 'delete', danger: true },
                  ]"
                  @action="(key) => handleRowAction(key, row)"
                />
              </div>
            </template>
          </DataTable>
        </div>
      </div>

      <DataTablePagination
        :current-page="teams.meta.current_page" :last-page="teams.meta.last_page" :from="teams.meta.from"
        :to="teams.meta.to" :per-page="teams.meta.per_page" :total="teams.meta.total"
        @update:current-page="table.page.value = $event" @update:per-page="table.perPage.value = $event"
      />
    </div>

    <SlideOver
      :open="modalMode !== null"
      :title="modalMode === 'create' ? 'Add Campaign Team' : 'Edit Campaign Team'"
      size="md" side="right" @close="closeModal"
    >
      <CampaignTeamForm
        v-if="modalMode" :form="form" :submit-label="modalMode === 'create' ? 'Create team' : 'Save changes'"
        :aspirants="formOptions.aspirants" :potential-leads="formOptions.potentialLeads"
        @submit="submitForm" @cancel="closeModal"
      />
    </SlideOver>
      <ConfirmDialog
      :open="deleteDialog !== null"
      title="Confirm deletion"
      :description="deleteDialog?.message ?? ''"
      confirm-text="Delete"
      variant="danger"
      @confirm="confirmDelete"
      @cancel="closeDeleteDialog"
      @close="closeDeleteDialog"
    />
</AppLayout>
</template>