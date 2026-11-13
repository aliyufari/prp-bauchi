<script setup lang="ts">
/**
 * Single-level location picker for people who are assigned to ONE jurisdiction.
 *
 *  - state level -> nothing to choose (Bauchi is applied automatically)
 *  - zonal       -> only senatorial zones are listed
 *  - LGA         -> only LGAs are listed
 *  - ward        -> only wards are listed
 *  - polling unit-> only polling units are listed
 *
 * There is no parent -> child dependency: choosing a ward does not require
 * choosing a zone and an LGA first. The level either comes from the parent form
 * (`level` prop, e.g. derived from the coordinator type) or, when the form has
 * no level of its own, from a "Location level" select rendered here.
 */
import Alert from '@/components/ui/Alert.vue';
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import Select from '@/components/ui/form/Select.vue';
import type { SelectOption } from '@/components/ui/form/types';
import { useJurisdiction } from '@/composables/useJurisdiction';
import { deepestLevel, LEVEL_LABEL, LEVEL_ORDER } from '@/lib/location';
import type { LocationApiOption, LocationLevel, LocationModel } from '@/types/location';
import axios from 'axios';
import { Info } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: LocationModel;
        /** Level driven by the parent form. Leave empty to show the level picker. */
        level?: LocationLevel | null;
        /** Restrict the level picker (e.g. party agents only work at ward or polling unit). */
        levels?: LocationLevel[];
        errors?: Record<string, string | string[]>;
        disabled?: boolean;
        required?: boolean;
    }>(),
    { level: null, levels: () => LEVEL_ORDER, errors: () => ({}), disabled: false, required: false },
);

const emit = defineEmits<{
    'update:modelValue': [value: LocationModel];
    selected: [payload: { level: LocationLevel; option: LocationApiOption | null }];
}>();

const ROUTES: Record<LocationLevel, string> = {
    state: 'location-options.states',
    zone: 'location-options.zones',
    lga: 'location-options.lgas',
    ward: 'location-options.wards',
    pu: 'location-options.pus',
};

const { unrestricted, level: creatorLevel, isLocked, lockedValue } = useJurisdiction();

/* Levels the signed-in user may assign: strictly BELOW their own (the server enforces the same rule). */
const allowedLevels = computed<LocationLevel[]>(() => {
    const permitted = props.levels.filter((key) => LEVEL_ORDER.includes(key));
    if (unrestricted.value || !creatorLevel.value) return permitted;
    const own = LEVEL_ORDER.indexOf(creatorLevel.value);
    return permitted.filter((key) => LEVEL_ORDER.indexOf(key) > own);
});
const levelOptions = computed<SelectOption[]>(() => allowedLevels.value.map((key) => ({ value: key, label: LEVEL_LABEL[key] })));

const pickedLevel = ref<LocationLevel | null>(deepestLevel(props.modelValue));
const showPicker = computed(() => props.level == null);
const activeLevel = computed<LocationLevel | null>(() => props.level ?? pickedLevel.value);

const rows = ref<LocationApiOption[]>([]);
const loading = ref(false);
const requestError = ref('');
let requestId = 0;

const options = computed<SelectOption[]>(() =>
    rows.value.map((item) => ({
        value: String(item.id),
        label: item.name,
        description: item.code ?? undefined,
    })),
);

const fieldKey = computed(() => (activeLevel.value ? (`${activeLevel.value}_id` as const) : null));
const currentValue = computed(() => {
    const v = fieldKey.value ? props.modelValue[fieldKey.value] : null;
    return v == null || v === '' ? null : String(v);
});
const fieldError = computed(() => {
    if (fieldKey.value && props.errors[fieldKey.value]) return props.errors[fieldKey.value];
    // Backends often report the failure on whichever location column was invalid.
    return LEVEL_ORDER.map((key) => props.errors[`${key}_id`]).find(Boolean) ?? null;
});
const lockedHere = computed(() => !!activeLevel.value && isLocked(activeLevel.value));

function normalizeRows(payload: unknown): LocationApiOption[] {
    const list = Array.isArray(payload) ? payload : ((payload as { data?: unknown } | null)?.data ?? []);
    return (Array.isArray(list) ? (list as LocationApiOption[]) : []).filter((item) => item && item.id != null);
}

/** Only levels ABOVE the chosen one can scope a request, and only via the user's own locked jurisdiction. */
function scopeParams(level: LocationLevel, stateId: string | null) {
    const params: Record<string, string> = {};
    if (stateId && level !== 'state') params.state_id = stateId;
    for (const key of LEVEL_ORDER.slice(1, LEVEL_ORDER.indexOf(level))) {
        const locked = lockedValue(key);
        if (locked) params[`${key}_id`] = String(locked);
    }
    return params;
}

async function fetchRows(level: LocationLevel, stateId: string | null): Promise<LocationApiOption[]> {
    const { data } = await axios.get(route(ROUTES[level]), { params: scopeParams(level, stateId) });
    return normalizeRows(data);
}

function patch(values: LocationModel) {
    emit('update:modelValue', { ...props.modelValue, ...values });
}

/** Deployment is fixed to Bauchi: resolve its id silently and keep it on the model. */
async function resolveState(): Promise<string | null> {
    const existing = props.modelValue.state_id;
    if (existing != null && existing !== '') return String(existing);
    const [bauchi] = await fetchRows('state', null);
    if (!bauchi) throw new Error('state-unavailable');
    patch({ state_id: String(bauchi.id) });
    return String(bauchi.id);
}

async function load() {
    const level = activeLevel.value;
    const id = ++requestId;
    rows.value = [];
    requestError.value = '';
    if (!level) return;

    loading.value = true;
    try {
        const stateId = await resolveState();
        if (level !== 'state') {
            const list = await fetchRows(level, stateId);
            if (id !== requestId) return;
            rows.value = list;

            // A locked level (the signed-in user's own jurisdiction) is pre-selected.
            const locked = lockedValue(level);
            if (locked && currentValue.value !== String(locked)) patch({ [`${level}_id`]: String(locked) });
        }
    } catch {
        if (id === requestId) requestError.value = `Unable to load ${LEVEL_LABEL[level].toLowerCase()} options. Please try again.`;
    } finally {
        if (id === requestId) loading.value = false;
    }
}

function select(value: string | number | null) {
    const level = activeLevel.value;
    if (!level) return;
    const option = value == null ? null : (rows.value.find((item) => String(item.id) === String(value)) ?? null);

    // Only the chosen level is set. Ancestor ids are copied across when the API
    // supplies them, so the record can still be filtered by zone / LGA / ward.
    const next: LocationModel = { zone_id: null, lga_id: null, ward_id: null, pu_id: null };
    if (option) {
        for (const key of ['zone', 'lga', 'ward'] as const) {
            const ancestor = (option as Record<string, unknown>)[`${key}_id`];
            if (LEVEL_ORDER.indexOf(key) < LEVEL_ORDER.indexOf(level) && ancestor != null) next[`${key}_id`] = String(ancestor);
        }
    }
    next[`${level}_id`] = value == null ? null : String(value);
    patch(next);
    emit('selected', { level, option });
}

function pickLevel(value: string | number | null) {
    pickedLevel.value = (value as LocationLevel | null) ?? null;
}

// Changing the level wipes the previous selection (the old id belongs to another level).
watch(activeLevel, (next, previous) => {
    if (next === previous) return;
    patch({ zone_id: null, lga_id: null, ward_id: null, pu_id: null });
    load();
});

onMounted(load);
</script>

<template>
    <div class="space-y-4">
        <Select
            v-if="showPicker"
            :model-value="pickedLevel"
            :options="levelOptions"
            label="Location level"
            placeholder="Select location level"
            :disabled="disabled"
            :required="required"
            hint="Where this person operates. Only that level needs to be chosen."
            @update:model-value="pickLevel"
        />

        <p v-if="!activeLevel" class="flex items-center gap-2 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3 py-2.5 text-xs text-gray-500">
            <Info class="h-4 w-4 shrink-0 text-gray-400" />
            Select a level first to choose the location.
        </p>

        <p
            v-else-if="activeLevel === 'state'"
            class="flex items-center gap-2 rounded-xl border border-green-100 bg-green-50 px-3 py-2.5 text-xs font-medium text-green-800"
        >
            <Info class="h-4 w-4 shrink-0 text-green-700" />
            State level covers all of Bauchi State. No further location is needed.
        </p>

        <SearchSelect
            v-else
            :model-value="currentValue"
            :options="options"
            :label="LEVEL_LABEL[activeLevel]"
            :placeholder="loading ? 'Loading…' : `Select ${LEVEL_LABEL[activeLevel].toLowerCase()}`"
            :search-placeholder="`Search ${LEVEL_LABEL[activeLevel].toLowerCase()}…`"
            :disabled="disabled || loading || lockedHere"
            :required="required"
            :error="fieldError"
            @update:model-value="select"
        />

        <p v-if="activeLevel && activeLevel !== 'state' && !requestError" class="-mt-2 text-xs text-gray-400">
            Only {{ LEVEL_LABEL[activeLevel].toLowerCase() }}s are listed. No other selection is required.
        </p>

        <Alert v-if="requestError" variant="error" title="Location options unavailable">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span>{{ requestError }}</span>
                <button type="button" class="font-semibold underline underline-offset-2" @click="load">Retry</button>
            </div>
        </Alert>
    </div>
</template>
