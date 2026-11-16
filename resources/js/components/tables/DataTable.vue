<script setup lang="ts" generic="T extends Record<string, any>">
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import DataTableEmpty from './DataTableEmpty.vue';

export interface Column {
    key: string;
    label: string;
    sortable?: boolean;
    sortKey?: string;
    slot?: string;
    class?: string;
    hideOnMobile?: boolean;
    /** Accessible label for columns that contain icon-only controls. */
    ariaLabel?: string;
    /** Minimum column width, e.g. `12rem`. Keeps content readable; the table scrolls sideways instead of squashing. */
    minWidth?: string;
    align?: 'left' | 'center' | 'right';
    /** Pins the column to the left while scrolling horizontally. */
    sticky?: boolean;
    /** Hides the column on screens narrower than `lg` (it stays in the mobile cards). */
    hideOnTablet?: boolean;
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
    /** Keeps the header visible while the body scrolls (set `maxHeight` too). */
    stickyHeader?: boolean;
    /** CSS max-height of the scroll area, e.g. `70vh`. Unset = grows with its rows. */
    maxHeight?: string;
    density?: 'comfortable' | 'compact';
    /** Highlights rows on hover and emits `row-click` when a row is clicked. */
    clickable?: boolean;
    /** How many placeholder rows to show while the first load is in flight. */
    skeletonRows?: number;
}

const props = withDefaults(defineProps<Props>(), {
    selected: () => [],
    rowKey: 'id',
    selectable: true,
    loading: false,
    emptyTitle: 'No records found',
    emptyDescription: 'Try changing your search or filters.',
    stickyHeader: true,
    density: 'comfortable',
    clickable: false,
    skeletonRows: 6,
});

const emit = defineEmits<{
    'toggle-selection': [id: string | number];
    'toggle-all': [];
    sort: [column: string];
    'row-click': [row: T];
}>();

const selectAllInput = ref<HTMLInputElement | null>(null);
const rowIds = computed(() => props.rows.map((row) => row[props.rowKey]).filter((id): id is string | number => id !== undefined && id !== null));
const selectedSet = computed(() => new Set(props.selected));
const selectedOnPage = computed(() => rowIds.value.filter((id) => selectedSet.value.has(id)).length);
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

const isSelected = (id: string | number) => selectedSet.value.has(id);
const sortKeyOf = (col: Column) => col.sortKey ?? col.key;
const isSorted = (col: Column) => props.sortBy === sortKeyOf(col);
const ariaSort = (col: Column) => (isSorted(col) ? (props.sortDirection === 'asc' ? 'ascending' : 'descending') : 'none');
const sort = (col: Column) => col.sortable && emit('sort', sortKeyOf(col));

const cellPad = computed(() => (props.density === 'compact' ? 'px-3 py-2' : 'px-4 py-3'));
const alignClass = (col: Column) => (col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left');
const colStyle = (col: Column) => (col.minWidth ? { minWidth: col.minWidth } : undefined);

// A sticky column needs an opaque background, otherwise scrolled content shows through it.
const stickyClass = (col: Column, head = false) =>
    col.sticky ? ['sticky left-0 z-[1]', head ? 'bg-gray-50' : 'bg-white group-hover:bg-neutral-50', 'shadow-[1px_0_0_0_#f3f4f6]'] : [];

const tableColumns = computed(() => props.columns);
const visibleOnTablet = (col: Column) => !col.hideOnTablet;

// Mobile cards: the member slot is the heading, actions the footer, the rest label/value rows.
const mobileColumns = computed(() => props.columns.filter((col) => !col.hideOnMobile && col.slot !== 'member' && col.slot !== 'actions'));
const identityColumn = computed(() => props.columns.find((col) => col.slot === 'member' && !col.hideOnMobile));
const actionsColumn = computed(() => props.columns.find((col) => col.slot === 'actions' && !col.hideOnMobile));

/* Edge shadows tell people there is more to scroll to. */
const scroller = ref<HTMLElement | null>(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);
function measure() {
    const el = scroller.value;
    if (!el) return;
    canScrollLeft.value = el.scrollLeft > 2;
    canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 2;
}
let observer: ResizeObserver | null = null;
onMounted(() => {
    measure();
    if (typeof ResizeObserver !== 'undefined' && scroller.value) {
        observer = new ResizeObserver(measure);
        observer.observe(scroller.value);
        const table = scroller.value.querySelector('table');
        if (table) observer.observe(table);
    }
});
onBeforeUnmount(() => observer?.disconnect());
watch(() => [props.rows, props.columns], () => nextTick(measure), { deep: false });

const showSkeleton = computed(() => props.loading && props.rows.length === 0);
const skeletonList = computed(() => Array.from({ length: props.skeletonRows }, (_, i) => i));
</script>

<template>
    <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" :aria-busy="loading">
        <!-- Thin progress bar: results stay visible while the next page loads. -->
        <div v-if="loading" class="absolute inset-x-0 top-0 z-30 h-0.5 overflow-hidden bg-red-100" role="status" aria-label="Loading records">
            <div class="h-full w-1/3 animate-[dt-slide_1.1s_ease-in-out_infinite] rounded-full bg-red-600" />
        </div>

        <!-- Table (md and up) -->
        <div class="relative hidden md:block">
            <div v-if="canScrollLeft" class="pointer-events-none absolute inset-y-0 left-0 z-20 w-5 bg-gradient-to-r from-black/10 to-transparent" />
            <div v-if="canScrollRight" class="pointer-events-none absolute inset-y-0 right-0 z-20 w-5 bg-gradient-to-l from-black/10 to-transparent" />

            <div ref="scroller" class="overflow-auto overscroll-x-contain" :style="{ maxHeight }" tabindex="0" role="region" aria-label="Data table, scrollable" @scroll.passive="measure">
                <table class="w-full min-w-[640px] border-separate border-spacing-0 text-left" :class="loading && !showSkeleton ? 'opacity-60 transition-opacity' : 'transition-opacity'">
                    <thead :class="[stickyHeader && 'sticky top-0 z-10']">
                        <tr>
                            <th v-if="selectable" scope="col" class="w-12 border-b border-gray-200 bg-gray-50" :class="cellPad">
                                <input
                                    ref="selectAllInput"
                                    type="checkbox"
                                    :checked="allSelected"
                                    :disabled="rows.length === 0 || loading"
                                    aria-label="Select all records on this page"
                                    class="h-4 w-4 rounded border-gray-300 text-red-700 focus:ring-2 focus:ring-red-600/25 disabled:opacity-40"
                                    @change="emit('toggle-all')"
                                />
                            </th>
                            <th
                                v-for="col in tableColumns"
                                :key="col.key"
                                scope="col"
                                :aria-sort="col.sortable ? ariaSort(col) : undefined"
                                :style="colStyle(col)"
                                class="border-b border-gray-200 bg-gray-50 text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500 uppercase"
                                :class="[cellPad, alignClass(col), stickyClass(col, true), !visibleOnTablet(col) && 'max-lg:hidden', col.class]"
                            >
                                <button
                                    v-if="col.sortable"
                                    type="button"
                                    class="group/sort inline-flex items-center gap-1.5 rounded-sm transition outline-none hover:text-red-800 focus-visible:ring-2 focus-visible:ring-red-600/30"
                                    :class="isSorted(col) && 'text-red-800'"
                                    :aria-label="`Sort by ${col.label}`"
                                    @click="sort(col)"
                                >
                                    {{ col.label }}
                                    <ArrowUp v-if="isSorted(col) && sortDirection === 'asc'" class="h-3.5 w-3.5" />
                                    <ArrowDown v-else-if="isSorted(col)" class="h-3.5 w-3.5" />
                                    <ArrowUpDown v-else class="h-3.5 w-3.5 text-gray-300 group-hover/sort:text-red-600" />
                                </button>
                                <span v-else :aria-label="col.ariaLabel">{{ col.label }}</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <template v-if="showSkeleton">
                            <tr v-for="n in skeletonList" :key="`sk-${n}`" aria-hidden="true">
                                <td v-if="selectable" class="border-b border-gray-100" :class="cellPad"><div class="h-4 w-4 animate-pulse rounded bg-gray-100" /></td>
                                <td v-for="col in tableColumns" :key="col.key" class="border-b border-gray-100" :class="[cellPad, !visibleOnTablet(col) && 'max-lg:hidden']">
                                    <div class="h-4 animate-pulse rounded bg-gray-100" :style="{ width: `${50 + ((n * 17 + col.key.length * 11) % 45)}%` }" />
                                </td>
                            </tr>
                        </template>

                        <template v-else>
                        <tr
                            v-for="row in rows"
                            :key="row[rowKey]"
                            class="group transition-colors hover:bg-neutral-50/40"
                            :class="[isSelected(row[rowKey]) && 'bg-neutral-50/60', clickable && 'cursor-pointer']"
                            @click="clickable && emit('row-click', row)"
                        >
                            <td v-if="selectable" class="w-12 border-b border-gray-100" :class="cellPad" @click.stop>
                                <input
                                    type="checkbox"
                                    :checked="isSelected(row[rowKey])"
                                    :aria-label="`Select record ${row[rowKey]}`"
                                    class="h-4 w-4 rounded border-gray-300 text-red-700 focus:ring-2 focus:ring-red-600/25"
                                    @change="emit('toggle-selection', row[rowKey])"
                                />
                            </td>
                            <td
                                v-for="col in tableColumns"
                                :key="col.key"
                                class="border-b border-gray-100 align-middle"
                                :style="colStyle(col)"
                                :class="[cellPad, alignClass(col), stickyClass(col), !visibleOnTablet(col) && 'max-lg:hidden', col.slot === 'actions' && 'text-right', col.class]"
                                @click="col.slot === 'actions' && $event.stopPropagation()"
                            >
                                <slot v-if="col.slot" :name="col.slot" :row="row" />
                                <span v-else class="text-sm text-gray-700">{{ row[col.key] ?? '—' }}</span>
                            </td>
                        </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cards (below md) keep the identity, data and action slots. -->
        <div class="divide-y divide-gray-100 md:hidden" :class="loading && !showSkeleton && 'opacity-60 transition-opacity'">
            <template v-if="showSkeleton">
                <div v-for="n in Math.min(skeletonRows, 4)" :key="`skc-${n}`" class="space-y-3 p-4" aria-hidden="true">
                    <div class="flex items-center gap-3"><div class="h-10 w-10 animate-pulse rounded-full bg-gray-100" /><div class="h-4 w-1/2 animate-pulse rounded bg-gray-100" /></div>
                    <div class="h-3 w-full animate-pulse rounded bg-gray-100" />
                    <div class="h-3 w-2/3 animate-pulse rounded bg-gray-100" />
                </div>
            </template>
            <template v-else>
            <article
                v-for="row in rows"
                :key="row[rowKey]"
                class="p-4 transition-colors"
                :class="[isSelected(row[rowKey]) ? 'bg-neutral-50/60' : 'active:bg-neutral-50/30', clickable && 'cursor-pointer']"
                @click="clickable && emit('row-click', row)"
            >
                <div class="flex items-start gap-3">
                    <input
                        v-if="selectable"
                        type="checkbox"
                        :checked="isSelected(row[rowKey])"
                        :aria-label="`Select record ${row[rowKey]}`"
                        class="mt-1 h-4 w-4 shrink-0 rounded border-gray-300 text-red-700 focus:ring-2 focus:ring-red-600/25"
                        @click.stop
                        @change="emit('toggle-selection', row[rowKey])"
                    />
                    <div class="min-w-0 flex-1">
                        <div v-if="identityColumn" class="min-w-0"><slot :name="identityColumn.slot" :row="row" /></div>
                        <dl v-if="mobileColumns.length" class="mt-3 space-y-2" :class="identityColumn ? 'border-t border-gray-100 pt-3' : ''">
                            <div v-for="col in mobileColumns" :key="col.key" class="flex items-start justify-between gap-3 text-xs">
                                <dt class="shrink-0 font-medium text-gray-500">{{ col.label }}</dt>
                                <dd v-if="!col.slot" class="min-w-0 text-right font-medium break-words text-gray-700">{{ row[col.key] ?? '—' }}</dd>
                                <dd v-else class="min-w-0 text-right"><slot :name="col.slot" :row="row" /></dd>
                            </div>
                        </dl>
                        <div v-if="actionsColumn" class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-3" @click.stop>
                            <slot :name="actionsColumn.slot" :row="row" />
                        </div>
                    </div>
                </div>
            </article>
            </template>
        </div>

        <DataTableEmpty v-if="!loading && rows.length === 0" :title="emptyTitle" :description="emptyDescription" />
    </div>
</template>

<style scoped>
@keyframes dt-slide {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}
</style>
