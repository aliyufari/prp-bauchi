<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import { useToast } from '@/composables/useToast'
import ActionDropdown from '@/components/tables/ActionDropdown.vue';
import BulkActions from '@/components/tables/BulkActions.vue';
import DataTable from '@/components/tables/DataTable.vue';
import DataTablePagination from '@/components/tables/DataTablePagination.vue';
import DataTableToolbar from '@/components/tables/DataTableToolbar.vue';
import StatusBadge from '@/components/tables/StatusBadge.vue';
import UserAvatarCell from '@/components/tables/UserAvatarCell.vue';
import ConfirmDialog from '@/components/ui/overlay/ConfirmDialog.vue';
import SlideOver from '@/components/ui/SlideOver.vue';
import { useDataTable } from '@/composables/table/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Coordinator, CoordinatorFilterOptions, CoordinatorStatistics, CoordinatorTypeOption } from '@/types/coordinator';
import type { LocationOption } from '@/types/member';
import type { DataTableResponse } from '@/types/table';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { columns } from './columns';
import CoordinatorFilters from './components/CoordinatorFilters.vue';
import CoordinatorForm from './components/CoordinatorForm.vue';
import CoordinatorStats from './components/CoordinatorStats.vue';

interface FormOptions {
    states: LocationOption[];
    zones: LocationOption[];
    lgas: LocationOption[];
    wards: LocationOption[];
    pus: LocationOption[];
    types: CoordinatorTypeOption[];
}

interface Props {
    coordinators: DataTableResponse<Coordinator>;
    statistics: CoordinatorStatistics;
    filterOptions: CoordinatorFilterOptions;
    formOptions: FormOptions;
    filters: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
    filterOptions: () => ({ states: [], zones: [], lgas: [], wards: [], statuses: [], types: [] }),
    formOptions: () => ({ states: [], zones: [], lgas: [], wards: [], pus: [], types: [] }),
});

const table = useDataTable()
const toast = useToast();

table.search.value = props.filters?.search ?? '';
table.filters.value = {
    zone: props.filters?.zone ?? '',
    lga: props.filters?.lga ?? '',
    ward: props.filters?.ward ?? '',
    pu: props.filters?.pu ?? '',
    status: props.filters?.status ?? '',
    type: props.filters?.type ?? '',
};

function exportReport() {
    const params = {
        search: table.search.value || undefined,
        ...table.filters.value,
        sort_by: table.sortBy.value,
        sort_direction: table.sortDirection.value,
    }
    window.location.href = route('coordinators.export', params)
}

function query() {
    router.get(
        route('coordinators.index'),
        {
            search: table.search.value,
            ...table.filters.value,
            sort_by: table.sortBy.value,
            sort_direction: table.sortDirection.value,
            page: table.page.value,
            per_page: table.perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let debounce: ReturnType<typeof setTimeout>;
watch(table.search, () => {
    clearTimeout(debounce);
    table.page.value = 1;
    debounce = setTimeout(query, 350);
});
watch(
    table.filters,
    () => {
        table.page.value = 1;
        query();
    },
    { deep: true },
);
watch([table.sortBy, table.sortDirection, table.page, table.perPage], query);

/* ---------------------------------------------------------------------- */
/* Create / Edit modal                                                    */
/* ---------------------------------------------------------------------- */
type ModalMode = 'create' | 'edit' | null;
const modalMode = ref<ModalMode>(null);
const editingCoordinator = ref<Coordinator | null>(null);

function emptyFormValues() {
    return {
        first_name: '',
        avatar: null as File | null,
        middle_name: '' as string | null,
        last_name: '',
        email: '',
        password: '',
        phone: '' as string | null,
        gender: null as string | null,
        occupation: '' as string | null,
        education_status: '' as string | null,
        application_id: '' as string | null,
        type: '' as string | null,
        state_id: null as number | string | null,
        zone_id: null as number | string | null,
        lga_id: null as number | string | null,
        ward_id: null as number | string | null,
        pu_id: null as number | string | null,
    };
}

const form = useForm(emptyFormValues());

function openCreateModal() {
    editingCoordinator.value = null;
    form.clearErrors();
    form.defaults(emptyFormValues());
    form.reset();
    modalMode.value = 'create';
}

function openEditModal(row: Coordinator) {
    editingCoordinator.value = row;
    form.clearErrors();
    const values = {
        first_name: row.first_name,
        avatar: null,
        middle_name: row.middle_name,
        last_name: row.last_name,
        email: row.email,
        password: '',
        phone: row.phone,
        gender: row.gender,
        occupation: row.occupation,
        education_status: row.education_status,
        application_id: row.coordinator_no,
        type: row.coordinator_type,
        state_id: row.state_id,
        zone_id: row.zone_id,
        lga_id: row.lga_id,
        ward_id: row.ward_id,
        pu_id: row.pu_id,
    };
    form.defaults(values);
    form.reset();
    Object.assign(form, values);
    modalMode.value = 'edit';
}

function closeModal() {
    modalMode.value = null;
    editingCoordinator.value = null;
    form.clearErrors();
}

function submitForm() {
    const options = {
        preserveScroll: true,
        onSuccess: () => { closeModal(); toast.success('Changes saved successfully.'); router.reload() },
    };
    if (modalMode.value === 'create') {
        form.post(route('coordinators.store'), options);
    } else if (modalMode.value === 'edit' && editingCoordinator.value) {
        form.patch(route('coordinators.update', editingCoordinator.value.id), options);
    }
}

/* ---------------------------------------------------------------------- */
/* Row / bulk actions                                                     */
/* ---------------------------------------------------------------------- */
function viewCoordinator(row: Coordinator) {
    router.visit(route('coordinators.show', row.id));
}

function handleRowAction(key: string, row: Coordinator) {
    if (key === 'view') return viewCoordinator(row);
    if (key === 'edit') return openEditModal(row);
    if (key === 'delete') {
        requestDelete(`Delete ${row.first_name} ${row.last_name}?`, () => {
            router.delete(route('coordinators.destroy', row.id), { preserveScroll: true, onSuccess: () => { toast.success('Record deleted.'); router.reload() } });
        });
    }
}

function handleBulkAction(key: string) {
    if (key === 'delete') {
        requestDelete(`Delete ${table.selected.value.length} coordinators?`, () => {
            router.post(route('coordinators.bulk-destroy'), { ids: table.selected.value }, { preserveScroll: true, onSuccess: () => { table.clearSelection(); toast.success('Selected records deleted.'); router.reload() } });
        });
    }
}

function toggleAll() {
    const ids = props.coordinators.data.map((c) => c.id);
    table.togglePage(ids);
}

const deleteDialog = ref<{ message: string; action: () => void } | null>(null);
function requestDelete(message: string, action: () => void) {
    deleteDialog.value = { message, action };
}
function confirmDelete() {
    const pending = deleteDialog.value;
    deleteDialog.value = null;
    pending?.action();
}
function closeDeleteDialog() {
    deleteDialog.value = null;
}
</script>

<template>
    <Head title="Coordinators" />
    <AppLayout>
        <div class="space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-black text-gray-900 sm:text-xl">Coordinators</h2>
                    <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">Manage state, zone, LGA and ward-level coordinators</p>
                </div>
                <Button @click="openCreateModal" class="w-full sm:w-auto">
                    <Plus class="h-4 w-4" />
                    Add Coordinator
                </Button>
            </div>

            <!-- Stats -->
            <CoordinatorStats :statistics="statistics" />

            <!-- Toolbar + filters -->
            <DataTableToolbar
                v-model="table.search.value"
                placeholder="Search by name, email, phone or coordinator no..."
                show-export
                show-refresh
                @refresh="query"
                @export="exportReport"
            >
                <template #filters>
                    <CoordinatorFilters v-model="table.filters.value" :options="filterOptions" />
                </template>
            </DataTableToolbar>

            <!-- Bulk actions -->
            <BulkActions :selected="table.selected.value" @clear="table.clearSelection" @action="handleBulkAction" />

            <!-- Table -->
            <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:overflow-visible sm:px-0">
                <div class="min-w-0">
                    <DataTable
                        :columns="columns"
                        :rows="coordinators.data"
                        :selected="table.selected.value"
                        :sort-by="table.sortBy.value"
                        :sort-direction="table.sortDirection.value"
                        @toggle-selection="table.toggleSelection"
                        @toggle-all="toggleAll"
                        @sort="table.toggleSort"
                    >
                        <template #coordinator="{ row }">
                            <UserAvatarCell
                                :first-name="row.first_name"
                                :middle-name="row.middle_name"
                                :last-name="row.last_name"
                                :avatar-url="row.avatar_url"
                                :subtitle="row.coordinator_no"
                            />
                        </template>
                        <template #type="{ row }">
                            <span class="text-sm text-gray-700">{{ row.coordinator_type_label ?? '—' }}</span>
                        </template>
                        <template #location="{ row }">
                            <div class="text-sm text-gray-700">{{ row.lga ?? '—' }} LGA</div>
                            <div class="text-xs text-gray-400">{{ row.ward ?? '—' }} Ward</div>
                        </template>
                        <template #status="{ row }">
                            <StatusBadge :status="row.status" />
                        </template>
                        <template #actions="{ row }">
                            <div class="flex justify-end">
                                <ActionDropdown @action="(key) => handleRowAction(key, row)" />
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>

            <!-- Pagination -->
            <DataTablePagination
                :current-page="coordinators.meta.current_page"
                :last-page="coordinators.meta.last_page"
                :from="coordinators.meta.from"
                :to="coordinators.meta.to"
                :per-page="coordinators.meta.per_page"
                :total="coordinators.meta.total"
                @update:current-page="table.page.value = $event"
                @update:per-page="table.perPage.value = $event"
            />
        </div>

        <!-- Create / Edit modal -->
        <SlideOver
            :open="modalMode !== null"
            :title="modalMode === 'create' ? 'Add Coordinator' : 'Edit Coordinator'"
            :description="
                modalMode === 'create'
                    ? 'Register a new coordinator and assign their level.'
                    : editingCoordinator
                      ? `Update details for ${editingCoordinator.first_name} ${editingCoordinator.last_name}.`
                      : ''
            "
            size="lg"
            side="right"
            @close="closeModal"
        >
            <CoordinatorForm
                v-if="modalMode"
                :avatar-url="editingCoordinator?.avatar_url"
                :form="form"
                :submit-label="modalMode === 'create' ? 'Create coordinator' : 'Save changes'"
                :password-optional="modalMode === 'edit'"
                :types="formOptions.types"
                @submit="submitForm"
                @cancel="closeModal"
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
