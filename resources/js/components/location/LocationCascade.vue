<script setup lang="ts">
import Alert from '@/components/ui/Alert.vue';
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import type { SelectOption } from '@/components/ui/form/types';
import { useJurisdiction } from '@/composables/useJurisdiction';
import type { LocationApiOption, LocationLevel, LocationModel } from '@/types/location';
import axios from 'axios';
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: LocationModel;
        upTo?: LocationLevel;
        errors?: Record<string, string | string[]>;
        disabled?: boolean;
        required?: boolean;
    }>(),
    { upTo: 'ward', errors: () => ({}), disabled: false, required: false },
);

const emit = defineEmits<{
    'update:modelValue': [value: LocationModel];
    selected: [payload: { level: LocationLevel; option: LocationApiOption | null }];
}>();
const { isLocked, lockedValue } = useJurisdiction();

const levels: { key: LocationLevel; label: string; route: string; parent?: `${LocationLevel}_id` }[] = [
    { key: 'state', label: 'State', route: 'location-options.states' },
    { key: 'zone', label: 'Senatorial zone', route: 'location-options.zones', parent: 'state_id' },
    { key: 'lga', label: 'Local government area', route: 'location-options.lgas', parent: 'zone_id' },
    { key: 'ward', label: 'Ward', route: 'location-options.wards', parent: 'lga_id' },
    { key: 'pu', label: 'Polling unit', route: 'location-options.pus', parent: 'ward_id' },
];
const visible = computed(() => levels.slice(1, levels.findIndex((item) => item.key === props.upTo) + 1));
const options = reactive<Partial<Record<LocationLevel, SelectOption[]>>>({});
const rawOptions = reactive<Partial<Record<LocationLevel, LocationApiOption[]>>>({});
const loading = reactive<Record<LocationLevel, boolean>>({ state: false, zone: false, lga: false, ward: false, pu: false });
const loadVersion = reactive<Record<LocationLevel, number>>({ state: 0, zone: 0, lga: 0, ward: 0, pu: 0 });
const requestError = ref('');
const current = (key: LocationLevel) => props.modelValue[`${key}_id`] ?? null;
const fieldError = (key: LocationLevel) => props.errors[`${key}_id`] ?? null;
const optionList = (key: LocationLevel) => options[key] ?? [];

function normalizeRows(payload: unknown): LocationApiOption[] {
    const rows = Array.isArray(payload)
        ? payload
        : payload && typeof payload === 'object' && 'data' in payload && Array.isArray((payload as { data: unknown }).data)
          ? (payload as { data: unknown[] }).data
          : [];
    return (rows as LocationApiOption[]).filter((item) => item && item.id != null);
}

function toSelectOptions(rows: LocationApiOption[]): SelectOption[] {
    return rows.map((item) => ({
        value: item.id,
        label: item.name,
        description: item.code ?? undefined,
    }));
}

async function load(index: number) {
    const level = levels[index];
    const parentId = level.parent ? props.modelValue[level.parent] : null;
    const version = ++loadVersion[level.key];
    if (level.parent && parentId == null) {
        options[level.key] = [];
        rawOptions[level.key] = [];
        loading[level.key] = false;
        return;
    }
    loading[level.key] = true;
    requestError.value = '';
    try {
        const { data } = await axios.get(route(level.route), {
            params: level.parent ? { [level.parent]: parentId } : {},
        });
        if (version === loadVersion[level.key]) {
            rawOptions[level.key] = normalizeRows(data);
            options[level.key] = toSelectOptions(rawOptions[level.key] ?? []);
        }
    } catch {
        if (version === loadVersion[level.key]) {
            options[level.key] = [];
            rawOptions[level.key] = [];
            requestError.value = `Unable to load ${level.label.toLowerCase()} options. Please try again.`;
        }
    } finally {
        if (version === loadVersion[level.key]) loading[level.key] = false;
    }
}

async function updateLevel(index: number, value: string | number | null) {
    const level = levels[index];
    if (String(current(level.key) ?? '') === String(value ?? '')) return;
    const next: LocationModel = { ...props.modelValue, [`${level.key}_id`]: value };
    for (const child of levels.slice(index + 1)) {
        next[`${child.key}_id`] = null;
        options[child.key] = [];
        rawOptions[child.key] = [];
        loadVersion[child.key]++;
        loading[child.key] = false;
    }
    emit('update:modelValue', next);
    const selectedOption = value == null ? null : (rawOptions[level.key]?.find((item) => String(item.id) === String(value)) ?? null);
    emit('selected', { level: level.key, option: selectedOption });
    // The model watcher below reloads the dependent chain after the Inertia form updates.
}

async function hydrate() {
    if (hydrating) return;
    hydrating = true;
    try {
        // The deployment is fixed to Bauchi: resolve its id silently and keep
        // it in the form model, without exposing a state selector to users.
        await load(0);
        const bauchi = rawOptions.state?.[0];
        if (!bauchi) {
            requestError.value = 'Bauchi State is unavailable. Please contact an administrator.';
            return;
        }

        const seeded: LocationModel = { ...props.modelValue };
        const previousState = seeded.state_id;
        let changed = String(previousState ?? '') !== String(bauchi.id);
        seeded.state_id = bauchi.id;

        if (previousState && String(previousState) !== String(bauchi.id)) {
            for (const level of levels.slice(2)) seeded[`${level.key}_id`] = null;
        }

        if (changed || previousState) {
            emit('update:modelValue', seeded);
            await nextTick();
        }

        for (let index = 1; index < visible.value.length + 1; index++) {
            if (index > 1 && seeded[`${levels[index - 1].key}_id`] == null) break;
            await load(index);
        }
    } finally {
        hydrating = false;
    }
}

let hydrating = false;
watch(() => props.upTo, hydrate);
watch(
    () => levels.map((level) => current(level.key)),
    async (next, previous) => {
        if (hydrating || next.every((value, index) => String(value ?? '') === String(previous?.[index] ?? ''))) return;
        await hydrate();
    },
    { flush: 'post' },
);
onMounted(hydrate);
</script>

<template>
    <div class="space-y-3">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div v-for="(level, visibleIndex) in visible" :key="level.key" :class="level.key === 'pu' ? 'sm:col-span-2' : ''">
                <SearchSelect
                    :model-value="current(level.key)"
                    :options="optionList(level.key)"
                    :label="level.label"
                    :placeholder="loading[level.key] ? `Loading ${level.label.toLowerCase()}…` : `Select ${level.label.toLowerCase()}`"
                    :search-placeholder="`Search ${level.label.toLowerCase()}…`"
                    :disabled="disabled || isLocked(level.key) || loading[level.key] || current(levels[visibleIndex].key) == null"
                    :required="required && visibleIndex === 0"
                    :error="fieldError(level.key)"
                    @update:model-value="updateLevel(visibleIndex + 1, $event)"
                />
                <p v-if="loading[level.key]" class="mt-1 text-xs text-gray-400">Loading options…</p>
            </div>
        </div>
        <Alert v-if="requestError" variant="error" title="Location options unavailable">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span>{{ requestError }}</span>
                <button type="button" class="font-semibold underline underline-offset-2" @click="hydrate">Retry</button>
            </div>
        </Alert>
        <p class="text-xs text-gray-500">State is set automatically to Bauchi. Choose the remaining location levels in order; changing a parent clears the selections below it.</p>
    </div>
</template>
