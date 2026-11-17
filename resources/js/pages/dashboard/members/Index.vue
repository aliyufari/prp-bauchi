<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import { useToast } from '@/composables/useToast'
import Can from '@/components/access/Can.vue';
import ActionDropdown from '@/components/tables/ActionDropdown.vue';
import BulkActions from '@/components/tables/BulkActions.vue';
import DataTable from '@/components/tables/DataTable.vue';
import DataTablePagination from '@/components/tables/DataTablePagination.vue';
import DataTableToolbar from '@/components/tables/DataTableToolbar.vue';
import StatusBadge from '@/components/tables/StatusBadge.vue';
import UserAvatarCell from '@/components/tables/UserAvatarCell.vue';
import ConfirmDialog from '@/components/ui/overlay/ConfirmDialog.vue';
import { useDataTable } from '@/composables/table/useDataTable';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { rowActions } from '@/lib/rowActions';
import type { FormOptions, Member, MemberFilterOptions, MemberStatistics } from '@/types/member';
import type { DataTableResponse } from '@/types/table';
import { Head, router } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { columns } from './columns';
import MemberFilters from './components/MemberFilters.vue';
import MemberStats from './components/MemberStats.vue';
import CreateMember from './CreateMember.vue';
import EditMember from './EditMember.vue';
import ViewMember from './ViewMember.vue';

interface Props {
    members: DataTableResponse<Member>;
    statistics: MemberStatistics;
    filterOptions: MemberFilterOptions;
    formOptions: FormOptions;
    filters: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
    filters: () => ({}),
    filterOptions: () => ({ states: [], zones: [], lgas: [], wards: [], statuses: [] }),
    formOptions: () => ({ states: [], zones: [], lgas: [], wards: [], pus: [], educationStatuses: [] }),
});

const { can } = usePermissions();
const table = useDataTable()
const toast = useToast();

table.search.value = props.filters?.search ?? '';
table.filters.value = {
    zone: props.filters?.zone ?? '',
    lga: props.filters?.lga ?? '',
    ward: props.filters?.ward ?? '',
    pu: props.filters?.pu ?? '',
    status: props.filters?.status ?? '',
};

function exportReport() {
    const params = {
        search: table.search.value || undefined,
        ...table.filters.value,
        sort_by: table.sortBy.value,
        sort_direction: table.sortDirection.value,
    }
    window.location.href = route('members.export', params)
}

function query() {
    router.get(
        route('members.index'),
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

/* Panel state: Create, Edit and View are independent */
const creating = ref(false);
const editingMember = ref<Member | null>(null);
const viewingMemberId = ref<number | string | null>(null);
const viewingMember = computed(() =>
    viewingMemberId.value === null ? null : (props.members.data.find((m) => m.id === viewingMemberId.value) ?? null),
);

const openCreate = () => (creating.value = true);
const closeCreate = () => (creating.value = false);
const openEdit = (m: Member) => (editingMember.value = m);
const closeEdit = () => (editingMember.value = null);
const openView = (m: Member) => (viewingMemberId.value = m.id);
const closeView = () => (viewingMemberId.value = null);
function editFromView(m: Member) {
    closeView();
    openEdit(m);
}

/* Row / bulk actions */
function handleRowAction(key: string, row: Member) {
    if (key === 'view') return openView(row);
    if (key === 'edit') return openEdit(row);
    if (key === 'assign') return openEdit(row);
    if (key === 'suspend') {
        const next = row.status !== 'active';
        requestAction(`${next ? 'Reactivate' : 'Suspend'} ${row.first_name} ${row.last_name}?`, next ? 'Reactivate' : 'Suspend', next ? 'primary' : 'danger', () => {
            router.patch(route('managed-users.status', { module: 'members', user: row.id }), { is_active: next }, { preserveScroll: true, onSuccess: () => { toast.success(next ? 'Account reactivated.' : 'Account suspended.'); router.reload() } });
        });
        return;
    }
    if (key === 'delete') {
        requestDelete(`Delete ${row.first_name} ${row.last_name}?`, () => {
            router.delete(route('members.destroy', row.id), { preserveScroll: true, onSuccess: () => { toast.success('Record deleted.'); router.reload() } });
        });
    }
}

function handleBulkAction(key: string) {
    if (key === 'delete') {
        requestDelete(`Delete ${table.selected.value.length} members?`, () => {
            router.post(route('members.bulk-destroy'), { ids: table.selected.value }, { preserveScroll: true, onSuccess: () => { table.clearSelection(); toast.success('Selected records deleted.'); router.reload() } });
        });
    }
}

function toggleAll() {
    const ids = props.members.data.map((m) => m.id);
    table.togglePage(ids);
}

const deleteDialog = ref<{ message: string; action: () => void; title?: string; confirmText?: string; variant?: 'danger' | 'primary' } | null>(null);
function requestDelete(message: string, action: () => void) {
    deleteDialog.value = { message, action };
}
function requestAction(message: string, confirmText: string, variant: 'danger' | 'primary', action: () => void) {
    deleteDialog.value = { message, action, title: `${confirmText} account`, confirmText, variant };
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
    <Head title="Members" />
    <AppLayout>
        <div class="space-y-4 sm:space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-black text-gray-900 sm:text-xl">Members</h2>
                    <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">Manage registered members across states, LGAs and wards</p>
                </div>
                <Can permission="members.create">
                    <Button @click="openCreate" class="w-full sm:w-auto">
                        <UserPlus class="h-4 w-4" />
                        Add Member
                    </Button>
                </Can>
            </div>

            <MemberStats :statistics="statistics" />

            <DataTableToolbar
                v-model="table.search.value"
                placeholder="Search by name, email, phone or member no..."
                :show-export="can('members.export')"
                show-refresh
                @refresh="query"
                @export="exportReport"
            >
                <template #filters>
                    <MemberFilters v-model="table.filters.value" :options="filterOptions" />
                </template>
            </DataTableToolbar>

            <BulkActions v-if="can('members.delete')" :selected="table.selected.value" @clear="table.clearSelection" @action="handleBulkAction" />

            <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:overflow-visible sm:px-0">
                <div class="min-w-0">
                    <DataTable
                        :columns="columns"
                        :rows="members.data"
                        :selectable="can('members.delete')"
                        :selected="table.selected.value"
                        :sort-by="table.sortBy.value"
                        :sort-direction="table.sortDirection.value"
                        @toggle-selection="table.toggleSelection"
                        @toggle-all="toggleAll"
                        @sort="table.toggleSort"
                    >
                        <template #member="{ row }">
                            <UserAvatarCell
                                :first-name="row.first_name"
                                :middle-name="row.middle_name"
                                :last-name="row.last_name"
                                :avatar-url="row.avatar_url"
                                :subtitle="row.member_no"
                            />
                        </template>
                        <template #location="{ row }">
                            <div class="text-sm text-gray-700">{{ row.lga ? `${row.lga} LGA` : '—' }}</div>
                            <div class="text-xs text-gray-400">{{ row.ward ? `${row.ward} Ward` : '' }}</div>
                        </template>
                        <template #mentor="{ row }">
                            <span class="text-sm text-gray-700">{{ row.mentor ?? '—' }}</span>
                        </template>
                        <template #training="{ row }">
                            <StatusBadge :status="row.training_status_label" />
                        </template>
                        <template #status="{ row }">
                            <StatusBadge :status="row.status" />
                        </template>
                        <template #actions="{ row }">
                            <div class="flex justify-end">
                                <ActionDropdown :actions="rowActions(row.can, row.can?.update ? [
                                    { key: 'assign', label: 'Assign / change area', icon: 'assign' },
                                    { key: 'suspend', label: row.status === 'active' ? 'Suspend account' : 'Reactivate account', icon: 'suspend', danger: row.status === 'active' },
                                ] : [])" @action="(key) => handleRowAction(key, row)" />
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>

            <DataTablePagination
                :current-page="members.meta?.current_page ?? 1"
                :last-page="members.meta?.last_page ?? 1"
                :from="members.meta?.from ?? null"
                :to="members.meta?.to ?? null"
                :per-page="members.meta?.per_page ?? 15"
                :total="members.meta?.total ?? members.data.length"
                @update:current-page="table.page.value = $event"
                @update:per-page="table.perPage.value = $event"
            />
        </div>

        <CreateMember :open="creating" :form-options="formOptions" @close="closeCreate" />
        <EditMember :member="editingMember" :form-options="formOptions" @close="closeEdit" />
        <ViewMember v-if="viewingMember" :member="viewingMember" @close="closeView" @edit="editFromView" />
        <ConfirmDialog
            :open="deleteDialog !== null"
            :title="deleteDialog?.title ?? 'Confirm deletion'"
            :description="deleteDialog?.message ?? ''"
            :confirm-text="deleteDialog?.confirmText ?? 'Delete'"
            :variant="deleteDialog?.variant ?? 'danger'"
            @confirm="confirmDelete"
            @cancel="closeDeleteDialog"
            @close="closeDeleteDialog"
        />
    </AppLayout>
</template>
