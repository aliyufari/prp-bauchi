<script setup lang="ts">
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import type { SelectOption } from '@/components/ui/form/types';
import axios from 'axios';
import { SlidersHorizontal, X } from 'lucide-vue-next';
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';

export interface FilterOption {
    value: string;
    label: string;
}
export interface FilterDef {
    key: string;
    label: string;
    options: FilterOption[];
}

interface Props {
    filters: FilterDef[];
    modelValue: Record<string, string>;
}
const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: Record<string, string>] }>();

const locationOrder = ['state', 'zone', 'lga', 'ward', 'pu'] as const;
type LocationFilter = (typeof locationOrder)[number];
const endpoints: Record<LocationFilter, string> = {
    state: 'location-options.states',
    zone: 'location-options.zones',
    lga: 'location-options.lgas',
    ward: 'location-options.wards',
    pu: 'location-options.pus',
};
const locationOptions = reactive<Record<LocationFilter, SelectOption[]>>({ state: [], zone: [], lga: [], ward: [], pu: [] });
const operatingStateId = ref('');
const locationLoading = reactive<Record<LocationFilter, boolean>>({ state: false, zone: false, lga: false, ward: false, pu: false });
const requestVersion = reactive<Record<LocationFilter, number>>({ state: 0, zone: 0, lga: 0, ward: 0, pu: 0 });
const requestError = ref('');
const locationFilters = computed(() => props.filters.filter((filter) => locationOrder.includes(filter.key as LocationFilter)));
const activeCount = computed(() => props.filters.filter((filter) => Boolean(props.modelValue[filter.key])).length);

function isLocation(key: string): key is LocationFilter {
    return locationOrder.includes(key as LocationFilter);
}
function normalizeOptions(payload: unknown): SelectOption[] {
    const rows = Array.isArray(payload)
        ? payload
        : payload && typeof payload === 'object' && 'data' in payload && Array.isArray((payload as { data: unknown }).data)
          ? (payload as { data: unknown[] }).data
          : [];
    return (rows as Array<{ id: string | number; name: string; code?: string | null }>).map((row) => ({
        value: String(row.id),
        label: row.name,
        description: row.code ?? undefined,
    }));
}

async function loadLocation(key: LocationFilter, parentValue?: string) {
    const index = locationOrder.indexOf(key);
    const parentKey = index > 0 ? locationOrder[index - 1] : null;
    const parentId = parentKey === 'state' ? (parentValue ?? operatingStateId.value) : parentKey ? (parentValue ?? props.modelValue[parentKey] ?? '') : '';
    if (parentKey && !parentId) {
        locationOptions[key] = [];
        locationLoading[key] = false;
        return;
    }

    const version = ++requestVersion[key];
    locationLoading[key] = true;
    requestError.value = '';
    try {
        const parentParam = parentKey ? `${parentKey}_id` : null;
        const { data } = await axios.get(route(endpoints[key]), {
            params: parentParam ? { [parentParam]: parentId } : {},
        });
        if (version === requestVersion[key]) locationOptions[key] = normalizeOptions(data);
    } catch {
        if (version === requestVersion[key]) {
            locationOptions[key] = [];
            requestError.value = `Could not load ${key === 'lga' ? 'LGAs' : `${key[0].toUpperCase()}${key.slice(1)}s`}. Please retry.`;
        }
    } finally {
        if (version === requestVersion[key]) locationLoading[key] = false;
    }
}

async function hydrateLocationFilters() {
    if (!locationFilters.value.length) return;
    await loadLocation('state');
    operatingStateId.value = String(locationOptions.state[0]?.value ?? '');
    for (let index = 1; index < locationOrder.length; index++) {
        const key = locationOrder[index];
        const parent = locationOrder[index - 1];
        if (!locationFilters.value.some((filter) => filter.key === key)) continue;
        const parentValue = parent === 'state' ? operatingStateId.value : props.modelValue[parent];
        if (!parentValue) break;
        await loadLocation(key, parentValue);
    }
}

function set(key: string, rawValue: string | number | null) {
    const value = rawValue == null ? '' : String(rawValue);
    const next = { ...props.modelValue, [key]: value };

    if (isLocation(key)) {
        const index = locationOrder.indexOf(key);
        for (const child of locationOrder.slice(index + 1)) {
            next[child] = '';
            locationOptions[child] = [];
            requestVersion[child]++;
            locationLoading[child] = false;
        }
    }

    emit('update:modelValue', next);
    if (isLocation(key) && value) {
        const child = locationOrder[locationOrder.indexOf(key) + 1];
        if (child && locationFilters.value.some((filter) => filter.key === child)) void loadLocation(child, value);
    }
}

function clearAll() {
    const cleared = { ...props.modelValue };
    props.filters.forEach((filter) => {
        cleared[filter.key] = '';
    });
    locationOrder.forEach((key) => {
        locationOptions[key] = [];
        requestVersion[key]++;
        locationLoading[key] = false;
    });
    emit('update:modelValue', cleared);
    if (locationFilters.value.length) void nextTick(hydrateLocationFilters);
}

watch(() => props.filters.map((filter) => filter.key).join('|'), hydrateLocationFilters);
onMounted(hydrateLocationFilters);
</script>

<template>
    <div v-if="filters.length" class="flex flex-wrap items-center gap-2" aria-label="Table filters">
        <span class="hidden shrink-0 items-center gap-1.5 text-xs font-semibold text-gray-500 sm:inline-flex">
            <SlidersHorizontal class="h-3.5 w-3.5" /> Filters
        </span>

        <template v-for="filter in filters" :key="filter.key">
            <SearchSelect
                v-if="isLocation(filter.key)"
                :model-value="modelValue[filter.key] || null"
                :options="locationOptions[filter.key]"
                :placeholder="locationLoading[filter.key] ? 'Loading…' : filter.label"
                :search-placeholder="`Search ${filter.label.toLowerCase()}…`"
                :empty-text="`No ${filter.label.toLowerCase()} found`"
                :disabled="
                    locationLoading[filter.key] ||
                    (filter.key === 'zone' ? !operatingStateId : !modelValue[locationOrder[locationOrder.indexOf(filter.key as LocationFilter) - 1]])
                "
                class="w-full min-w-[9rem] sm:w-auto sm:min-w-[10rem]"
                @update:model-value="set(filter.key, $event)"
            />
            <SearchSelect
                v-else
                :model-value="modelValue[filter.key] || null"
                :options="filter.options"
                :placeholder="filter.label"
                :search-placeholder="`Search ${filter.label.toLowerCase()}…`"
                class="w-full min-w-[9rem] sm:w-auto sm:min-w-[10rem]"
                @update:model-value="set(filter.key, $event)"
            />
        </template>

        <button
            v-if="activeCount"
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-2 text-xs font-semibold text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
            @click="clearAll"
        >
            <X class="h-3.5 w-3.5" /> Clear filters
        </button>
        <p v-if="requestError" class="w-full text-xs text-red-600" role="alert">
            {{ requestError }} <button type="button" class="font-semibold underline" @click="hydrateLocationFilters">Retry</button>
        </p>
    </div>
</template>
