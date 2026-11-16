<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import { useToast } from '@/composables/useToast'
import StatCard from '@/components/dashboard/StatCard.vue';
import ActionDropdown from '@/components/tables/ActionDropdown.vue';
import DataTable from '@/components/tables/DataTable.vue';
import DataTableFilters, { type FilterDef } from '@/components/tables/DataTableFilters.vue';
import DataTablePagination from '@/components/tables/DataTablePagination.vue';
import DataTableToolbar from '@/components/tables/DataTableToolbar.vue';
import ConfirmDialog from '@/components/ui/overlay/ConfirmDialog.vue';
import SlideOver from '@/components/ui/SlideOver.vue';
import { useDataTable } from '@/composables/table/useDataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import type { LocationOption, OfficeOption, PartyOption, Result, ResultFilterOptions, ResultStatistics } from '@/types/result';
import type { DataTableResponse } from '@/types/table';
import { Head, router, useForm } from '@inertiajs/vue3';
import { FileBarChart, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { columns } from './columns';
import ResultForm from './components/ResultForm.vue';

interface FormOptions {
    offices: OfficeOption[];
    parties: PartyOption[];
    states: LocationOption[];
}

interface Props {
    results: DataTableResponse<Result>;
    statistics: ResultStatistics;
    filterOptions: ResultFilterOptions;
    formOptions: FormOptions;
    filters: Record<string, string>;
    canUpload: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
    filterOptions: () => ({ offices: [], statuses: [], states: [] }),
    formOptions: () => ({ offices: [], parties: [], states: [] }),
});

const table = useDataTable()
const toast = useToast();
table.search.value = props.filters?.search ?? '';
table.filters.value = {
    office: props.filters?.office ?? '',
    status: props.filters?.status ?? '',
    zone: props.filters?.zone ?? '',
    lga: props.filters?.lga ?? '',
    ward: props.filters?.ward ?? '',
    pu: props.filters?.pu ?? '',
};

const filterDefs = computed<FilterDef[]>(() => [
    { key: 'office', label: 'Office', options: props.filterOptions.offices },
    { key: 'status', label: 'Status', options: props.filterOptions.statuses },
    { key: 'zone', label: 'Zone', options: [] },
    { key: 'lga', label: 'LGA', options: [] },
    { key: 'ward', label: 'Ward', options: [] },
    { key: 'pu', label: 'Polling unit', options: [] },
]);

function query() {
    router.get(
        route('results.index'),
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

/* ------------------------------------------------------------------ */
const modalOpen = ref(false);
const editingResult = ref<Result | null>(null);

function emptyForm() {
    const partyVotes: Record<string, number> = {};
    props.formOptions.parties.forEach((p) => {
        partyVotes[p.value] = 0;
    });
    return {
        state_id: null as string | null,
        zone_id: null as string | null,
        lga_id: null as string | null,
        ward_id: null as string | null,
        pu_id: null as string | null,
        office: '' as string | null,
        election_date: '' as string | null,
        registered_voters: 0,
        accredited_voters: 0,
        rejected_votes: 0,
        party_votes: partyVotes,
        result_sheet_url: '' as string | null,
        remarks: '' as string | null,
    };
}

const form = useForm(emptyForm());

function openUpload() {
    editingResult.value = null;
    form.clearErrors();
    form.defaults(emptyForm());
    form.reset();
    modalOpen.value = true;
}

function openEdit(row: Result) {
    editingResult.value = row;
    form.clearErrors();
    const partyVotes: Record<string, number> = {};
    props.formOptions.parties.forEach((p) => {
        partyVotes[p.value] = row.party_votes[p.value] ?? 0;
    });
    const values = {
        state_id: null,
        zone_id: null,
        lga_id: null,
        ward_id: null,
        pu_id: row.pu_id,
        office: row.office,
        election_date: row.election_date,
        registered_voters: row.registered_voters,
        accredited_voters: row.accredited_voters,
        rejected_votes: row.rejected_votes,
        party_votes: partyVotes,
        result_sheet_url: row.result_sheet_url,
        remarks: row.remarks,
    };
    form.defaults(values);
    form.reset();
    Object.assign(form, values);
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    editingResult.value = null;
    form.clearErrors();
}

function submit() {
    const options = { preserveScroll: true, onSuccess: () => { closeModal(); toast.success('Changes saved successfully.'); router.reload() } };
    if (editingResult.value) form.patch(route('results.update', editingResult.value.id), options);
    else form.post(route('results.store'), options);
}

function verify(row: Result, status: 'verified' | 'disputed') {
    router.patch(route('results.verify', row.id), { status }, { preserveScroll: true });
}

function remove(row: Result) {
    requestDelete(`Delete the result uploaded for ${row.pu}?`, () => {
        router.delete(route('results.destroy', row.id), { preserveScroll: true, onSuccess: () => { toast.success('Record deleted.'); router.reload() } });
    });
}

function handleRowAction(key: string, row: Result) {
    if (key === 'edit') return openEdit(row);
    if (key === 'verify') return verify(row, 'verified');
    if (key === 'dispute') return verify(row, 'disputed');
    if (key === 'delete') return remove(row);
}

function actionsFor(row: Result) {
    return [
        ...(row.can_edit ? [{ key: 'edit', label: 'Edit', icon: 'edit' as const }] : []),
        ...(row.can_verify
            ? [
                  { key: 'verify', label: 'Mark verified', icon: 'eye' as const },
                  { key: 'dispute', label: 'Flag disputed', icon: 'suspend' as const },
              ]
            : []),
        ...(row.can_edit ? [{ key: 'delete', label: 'Delete', icon: 'delete' as const, danger: true }] : []),
    ];
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
    <Head title="Results Management" />
    <AppLayout>
        <div class="space-y-4 sm:space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-black text-gray-900 sm:text-xl">Results Management</h2>
                    <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">Upload and verify election results by polling unit</p>
                </div>
                <Button v-if="canUpload" @click="openUpload" class="w-full sm:w-auto">
                    <Plus class="h-4 w-4" />
                    Upload Result
                </Button>
            </div>

            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <StatCard :value="statistics.total" label="Total Results" icon="FileBarChart" icon-bg="bg-red-100" icon-color="text-red-700" />
                <StatCard :value="statistics.verified" label="Verified" icon="CheckCircle2" icon-bg="bg-neutral-50" icon-color="text-red-900" />
                <StatCard :value="statistics.pending" label="Pending" icon="Clock" icon-bg="bg-amber-100" icon-color="text-amber-700" />
                <StatCard :value="statistics.disputed" label="Disputed" icon="AlertTriangle" icon-bg="bg-red-100" icon-color="text-red-700" />
            </div>

            <DataTableToolbar v-model="table.search.value" placeholder="Search by polling unit name or code..." show-refresh @refresh="query">
                <template #filters>
                    <DataTableFilters :filters="filterDefs" v-model="table.filters.value" />
                </template>
            </DataTableToolbar>

            <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:overflow-visible sm:px-0">
                <div class="min-w-[820px] sm:min-w-0">
                    <DataTable
                        :columns="columns"
                        :rows="results.data"
                        :selectable="false"
                        :sort-by="table.sortBy.value"
                        :sort-direction="table.sortDirection.value"
                        @sort="table.toggleSort"
                    >
                        <template #pu="{ row }">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-neutral-50 text-red-700">
                                    <FileBarChart class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ row.pu ?? '—' }}</p>
                                    <p v-if="row.pu_code" class="text-xs text-gray-400">{{ row.pu_code }}</p>
                                </div>
                            </div>
                        </template>
                        <template #office="{ row }">
                            <span class="text-sm text-gray-700">{{ row.office_label ?? '—' }}</span>
                        </template>
                        <template #location="{ row }">
                            <div class="text-sm text-gray-700">{{ row.lga ?? '—' }} LGA</div>
                            <div class="text-xs text-gray-400">{{ row.ward ?? '—' }} Ward</div>
                        </template>
                        <template #votes="{ row }">
                            <span class="text-sm font-semibold text-gray-700">{{ row.total_valid_votes.toLocaleString() }}</span>
                        </template>
                        <template #leading="{ row }">
                            <span class="text-sm text-gray-700">
                                {{ row.leading_party ?? '—' }}
                                <span v-if="row.leading_party" class="text-xs text-gray-400">({{ row.leading_votes.toLocaleString() }})</span>
                            </span>
                        </template>
                        <template #status="{ row }">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="{
                                    'bg-red-100 text-red-700': row.status === 'verified',
                                    'bg-amber-100 text-amber-700': row.status === 'pending',
                                    'bg-red-100 text-red-700': row.status === 'disputed',
                                }"
                            >
                                {{ row.status_label }}
                            </span>
                        </template>
                        <template #actions="{ row }">
                            <div class="flex justify-end">
                                <ActionDropdown :actions="actionsFor(row)" @action="(key) => handleRowAction(key, row)" />
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>

            <DataTablePagination
                :current-page="results.meta.current_page"
                :last-page="results.meta.last_page"
                :from="results.meta.from"
                :to="results.meta.to"
                :per-page="results.meta.per_page"
                :total="results.meta.total"
                @update:current-page="table.page.value = $event"
                @update:per-page="table.perPage.value = $event"
            />
        </div>

        <SlideOver
            :open="modalOpen"
            :title="editingResult ? 'Edit Result' : 'Upload Result'"
            description="Results are recorded per polling unit. You can only upload for locations within your jurisdiction."
            size="lg"
            side="right"
            @close="closeModal"
        >
            <ResultForm
                :form="form"
                :submit-label="editingResult ? 'Save changes' : 'Upload result'"
                :offices="formOptions.offices"
                :parties="formOptions.parties"
                :states="formOptions.states"
                @submit="submit"
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
