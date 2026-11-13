<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import { useToast } from '@/composables/useToast'
import StatCard from '@/components/dashboard/StatCard.vue';
import ActionDropdown from '@/components/tables/ActionDropdown.vue';
import BulkActions from '@/components/tables/BulkActions.vue';
import DataTable from '@/components/tables/DataTable.vue';
import DataTableFilters, { type FilterDef } from '@/components/tables/DataTableFilters.vue';
import DataTablePagination from '@/components/tables/DataTablePagination.vue';
import DataTableToolbar from '@/components/tables/DataTableToolbar.vue';
import ConfirmDialog from '@/components/ui/overlay/ConfirmDialog.vue';
import SlideOver from '@/components/ui/SlideOver.vue';
import { useDataTable } from '@/composables/table/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PollingUnit, PollingUnitFilterOptions, PollingUnitStatistics, WardOption } from '@/types/polling-unit';
import type { DataTableResponse } from '@/types/table';
import { Head, router, useForm } from '@inertiajs/vue3';
import { MapPin, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { columns } from './columns';
import PollingUnitForm from './components/PollingUnitForm.vue';

interface FormOptions {
    wards: WardOption[];
}

interface Props {
    pus: DataTableResponse<PollingUnit>;
    statistics: PollingUnitStatistics;
    filterOptions: PollingUnitFilterOptions;
    formOptions: FormOptions;
    filters: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
    filterOptions: () => ({ wards: [] }),
    formOptions: () => ({ wards: [] }),
});

const table = useDataTable()
const toast = useToast();

table.search.value = props.filters?.search ?? '';
table.filters.value = {
    zone: props.filters?.zone ?? '',
    lga: props.filters?.lga ?? '',
    ward: props.filters?.ward ?? '',
};

const filterDefs = computed<FilterDef[]>(() => [
    { key: 'zone', label: 'Zone', options: [] },
    { key: 'lga', label: 'LGA', options: [] },
    { key: 'ward', label: 'Ward', options: [] },
]);

function exportReport() {
    const params = {
        search: table.search.value || undefined,
        ...table.filters.value,
        sort_by: table.sortBy.value,
        sort_direction: table.sortDirection.value,
    }
    window.location.href = route('polling-units.export', params)
}

function query() {
    router.get(
        route('polling-units.index'),
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
type ModalMode = 'create' | 'edit' | null;
const modalMode = ref<ModalMode>(null);
const editingPu = ref<PollingUnit | null>(null);

function emptyFormValues() {
    return {
        name: '',
        code: '' as string | null,
        ward_id: null as string | null,
        address: '' as string | null,
        registered_voters: 0,
    };
}

const form = useForm(emptyFormValues());

function openCreateModal() {
    editingPu.value = null;
    form.clearErrors();
    form.defaults(emptyFormValues());
    form.reset();
    modalMode.value = 'create';
}

function openEditModal(row: PollingUnit) {
    editingPu.value = row;
    form.clearErrors();
    const values = {
        name: row.name,
        code: row.code,
        ward_id: row.ward_id,
        address: row.address,
        registered_voters: row.registered_voters,
    };
    form.defaults(values);
    form.reset();
    Object.assign(form, values);
    modalMode.value = 'edit';
}

function closeModal() {
    modalMode.value = null;
    editingPu.value = null;
    form.clearErrors();
}

function submitForm() {
    const options = { preserveScroll: true, onSuccess: () => { closeModal(); toast.success('Changes saved successfully.'); router.reload() } };
    if (modalMode.value === 'create') {
        form.post(route('polling-units.store'), options);
    } else if (modalMode.value === 'edit' && editingPu.value) {
        form.patch(route('polling-units.update', editingPu.value.id), options);
    }
}

function handleRowAction(key: string, row: PollingUnit) {
    if (key === 'edit') return openEditModal(row);
    if (key === 'delete') {
        requestDelete(`Delete polling unit "${row.name}"?`, () => {
            router.delete(route('polling-units.destroy', row.id), { preserveScroll: true, onSuccess: () => { toast.success('Record deleted.'); router.reload() } });
        });
    }
}

function handleBulkAction(key: string) {
    if (key === 'delete') {
        requestDelete(`Delete ${table.selected.value.length} polling units?`, () => {
            router.post(
                route('polling-units.bulk-destroy'),
                { ids: table.selected.value },
                { preserveScroll: true, onSuccess: () => { table.clearSelection(); toast.success('Selected records deleted.'); router.reload() } },
            );
        });
    }
}

function toggleAll() {
    const ids = props.pus.data.map((p) => p.id);
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
    <Head title="Polling Units" />
    <AppLayout>
        <div class="space-y-4 sm:space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-black text-gray-900 sm:text-xl">Polling Units</h2>
                    <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">Manage polling units, registered voters, and ward coverage</p>
                </div>
                <Button @click="openCreateModal" class="w-full sm:w-auto">
                    <Plus class="h-4 w-4" />
                    Add Polling Unit
                </Button>
            </div>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard :value="statistics.total" label="Total Polling Units" icon="MapPin" icon-bg="bg-green-100" icon-color="text-green-700" />
                <StatCard
                    :value="statistics.total_registered_voters"
                    label="Registered Voters"
                    icon="Users"
                    icon-bg="bg-green-50"
                    icon-color="text-green-900"
                />
                <StatCard
                    :value="statistics.wards_covered"
                    label="Wards Covered"
                    icon="Building2"
                    icon-bg="bg-gray-100"
                    icon-color="text-gray-700"
                />
                <StatCard
                    :value="statistics.unassigned"
                    label="Unassigned to Ward"
                    icon="AlertCircle"
                    icon-bg="bg-amber-100"
                    icon-color="text-amber-700"
                />
            </div>

            <DataTableToolbar
                v-model="table.search.value"
                placeholder="Search by name, code or address..."
                show-export
                show-refresh
                @refresh="query"
                @export="exportReport"
            >
                <template #filters>
                    <DataTableFilters :filters="filterDefs" v-model="table.filters.value" />
                </template>
            </DataTableToolbar>

            <BulkActions :selected="table.selected.value" @clear="table.clearSelection" @action="handleBulkAction" />

            <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:overflow-visible sm:px-0">
                <div class="min-w-[640px] sm:min-w-0">
                    <DataTable
                        :columns="columns"
                        :rows="pus.data"
                        :selected="table.selected.value"
                        :sort-by="table.sortBy.value"
                        :sort-direction="table.sortDirection.value"
                        @toggle-selection="table.toggleSelection"
                        @toggle-all="toggleAll"
                        @sort="table.toggleSort"
                    >
                        <template #pu="{ row }">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-700">
                                    <MapPin class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ row.name }}</p>
                                    <p v-if="row.code" class="text-xs text-gray-400">{{ row.code }}</p>
                                </div>
                            </div>
                        </template>
                        <template #ward="{ row }">
                            <span class="text-sm text-gray-700">{{ row.ward ?? '—' }}</span>
                        </template>
                        <template #voters="{ row }">
                            <span class="text-sm font-semibold text-gray-700">{{ row.registered_voters.toLocaleString() }}</span>
                        </template>
                        <template #agents="{ row }">
                            <span class="text-sm text-gray-700">{{ row.agents_deployed }}</span>
                        </template>
                        <template #actions="{ row }">
                            <div class="flex justify-end">
                                <ActionDropdown
                                    :actions="[
                                        { key: 'edit', label: 'Edit', icon: 'edit' },
                                        { key: 'delete', label: 'Delete', icon: 'delete', danger: true },
                                    ]"
                                    @action="(key) => handleRowAction(key, row)"
                                />
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>

            <DataTablePagination
                :current-page="pus.meta.current_page"
                :last-page="pus.meta.last_page"
                :from="pus.meta.from"
                :to="pus.meta.to"
                :per-page="pus.meta.per_page"
                :total="pus.meta.total"
                @update:current-page="table.page.value = $event"
                @update:per-page="table.perPage.value = $event"
            />
        </div>

        <SlideOver
            :open="modalMode !== null"
            :title="modalMode === 'create' ? 'Add Polling Unit' : 'Edit Polling Unit'"
            :description="
                modalMode === 'create' ? 'Register a new polling unit under a ward.' : editingPu ? `Update details for ${editingPu.name}.` : ''
            "
            size="md"
            side="right"
            @close="closeModal"
        >
            <PollingUnitForm
                v-if="modalMode"
                :form="form"
                :submit-label="modalMode === 'create' ? 'Create polling unit' : 'Save changes'"
                :wards="formOptions.wards"
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
