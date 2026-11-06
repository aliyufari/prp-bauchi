<script setup lang="ts" generic="T extends Record<string, any>">
import { ArrowDown, ArrowUp, ArrowUpDown, LoaderCircle } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import DataTableEmpty from './DataTableEmpty.vue';

export interface Column {
    key: string;
    label: string;
    sortable?: boolean;
    sortKey?: string;
    slot?: string;
    class?: string;
    hideOnMobile?: boolean;
    /** Optional accessible label for columns that contain icon-only controls. */
    ariaLabel?: string;
}

interface Props {
    columns: Column[];
    rows: T[];
    selected?: (string | number)[];
    sortBy?: string;
    sortDirection?: 'asc' | 'desc';
    rowKey?: string;
    selectable?: boolean;
    loading?: boolean;
    emptyTitle?: string;
    emptyDescription?: string;
    /** Shows a sticky table header when the table is inside a bounded scroll area. */
    stickyHeader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    selected: () => [],
    rowKey: 'id',
    selectable: true,
    loading: false,
    emptyTitle: 'No records found',
    emptyDescription: 'Try changing your search or filters.',
    stickyHeader: false,
});

const emit = defineEmits<{
    'toggle-selection': [id: string | number];
    'toggle-all': [];
    sort: [column: string];
}>();

const selectAllInput = ref<HTMLInputElement | null>(null);
const rowIds = computed(() => props.rows.map((row) => row[props.rowKey]).filter((id): id is string | number => id !== undefined && id !== null));
const selectedOnPage = computed(() => rowIds.value.filter((id) => props.selected.includes(id)).length);
const allSelected = computed(() => rowIds.value.length > 0 && selectedOnPage.value === rowIds.value.length);
const partiallySelected = computed(() => selectedOnPage.value > 0 && !allSelected.value);

watch(
    [allSelected, partiallySelected],
    async () => {
        await nextTick();
        if (selectAllInput.value) selectAllInput.value.indeterminate = partiallySelected.value;
    },
    { immediate: true },
);

function isSelected(id: string | number) {
    return props.selected.includes(id);
}
function toggleAll() {
    emit('toggle-all');
}
function sort(column: Column) {
    if (column.sortable) emit('sort', column.sortKey ?? column.key);
}

// Mobile layout treats the member slot as the identity heading and actions as its footer.
const mobileColumns = computed(() => props.columns.filter((col) => !col.hideOnMobile && col.slot !== 'member' && col.slot !== 'actions'));
const identityColumn = computed(() => props.columns.find((col) => col.slot === 'member' && !col.hideOnMobile));
const actionsColumn = computed(() => props.columns.find((col) => col.slot === 'actions' && !col.hideOnMobile));
</script>

<template>
    <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" :aria-busy="loading">
        <div class="overflow-x-auto">
            <table class="hidden w-full min-w-[640px] text-left md:table">
                <thead :class="[stickyHeader && 'sticky top-0 z-10']">
                    <tr class="border-b border-gray-200 bg-gray-50/90">
                        <th v-if="selectable" scope="col" class="w-12 px-4 py-3">
                            <input
                                ref="selectAllInput"
                                type="checkbox"
                                :checked="allSelected"
                                :disabled="rows.length === 0 || loading"
                                aria-label="Select all records on this page"
                                class="h-4 w-4 rounded border-gray-300 text-green-700 focus:ring-2 focus:ring-green-600/25 disabled:opacity-40"
                                @change="toggleAll"
                            />
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            scope="col"
                            class="px-4 py-3 text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500 uppercase"
                            :class="col.class"
                        >
                            <button
                                v-if="col.sortable"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-sm transition outline-none hover:text-green-800 focus-visible:ring-2 focus-visible:ring-green-600/30"
                                :aria-label="`Sort by ${col.label}`"
                                :aria-sort="sortBy === (col.sortKey ?? col.key) ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                @click="sort(col)"
                            >
                                {{ col.label }}
                                <ArrowUp v-if="sortBy === (col.sortKey ?? col.key) && sortDirection === 'asc'" class="h-3.5 w-3.5" />
                                <ArrowDown v-else-if="sortBy === (col.sortKey ?? col.key) && sortDirection === 'desc'" class="h-3.5 w-3.5" />
                                <ArrowUpDown v-else class="h-3.5 w-3.5 text-gray-300" />
                            </button>
                            <span v-else>{{ col.label }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in rows" :key="row[rowKey]" class="transition-colors hover:bg-green-50/30">
                        <td v-if="selectable" class="w-12 px-4 py-3">
                            <input
                                type="checkbox"
                                :checked="isSelected(row[rowKey])"
                                :aria-label="`Select record ${row[rowKey]}`"
                                class="h-4 w-4 rounded border-gray-300 text-green-700 focus:ring-2 focus:ring-green-600/25"
                                @change="emit('toggle-selection', row[rowKey])"
                            />
                        </td>
                        <td v-for="col in columns" :key="col.key" class="px-4 py-3 align-middle" :class="col.class">
                            <slot v-if="col.slot" :name="col.slot" :row="row" />
                            <span v-else class="text-sm text-gray-700">{{ row[col.key] ?? '—' }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards preserve custom identity, data and action slots. -->
        <div class="divide-y divide-gray-100 md:hidden">
            <article v-for="row in rows" :key="row[rowKey]" class="p-4 transition-colors hover:bg-green-50/20">
                <div class="flex items-start gap-3">
                    <input
                        v-if="selectable"
                        type="checkbox"
                        :checked="isSelected(row[rowKey])"
                        :aria-label="`Select record ${row[rowKey]}`"
                        class="mt-1 h-4 w-4 shrink-0 rounded border-gray-300 text-green-700 focus:ring-2 focus:ring-green-600/25"
                        @change="emit('toggle-selection', row[rowKey])"
                    />
                    <div class="min-w-0 flex-1">
                        <div v-if="identityColumn" class="min-w-0">
                            <slot :name="identityColumn.slot" :row="row" />
                        </div>
                        <div v-if="mobileColumns.length" class="mt-3 space-y-2" :class="identityColumn ? 'border-t border-gray-100 pt-3' : ''">
                            <div v-for="col in mobileColumns" :key="col.key" class="flex items-start justify-between gap-3 text-xs">
                                <span class="shrink-0 font-medium text-gray-500">{{ col.label }}</span>
                                <span v-if="!col.slot" class="min-w-0 text-right font-medium text-gray-700">{{ row[col.key] ?? '—' }}</span>
                                <span v-else class="min-w-0 text-right"><slot :name="col.slot" :row="row" /></span>
                            </div>
                        </div>
                        <div v-if="actionsColumn" class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-3">
                            <slot :name="actionsColumn.slot" :row="row" />
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-if="loading"
            class="absolute inset-0 z-20 flex items-center justify-center bg-white/75 backdrop-blur-[1px]"
            role="status"
            aria-live="polite"
        >
            <span
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 shadow-sm"
            >
                <LoaderCircle class="h-4 w-4 animate-spin text-green-700" /> Loading records…
            </span>
        </div>
        <DataTableEmpty v-if="!loading && rows.length === 0" :title="emptyTitle" :description="emptyDescription" />
    </div>
</template>
