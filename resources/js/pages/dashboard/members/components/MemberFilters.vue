<script setup lang="ts">
import DataTableFilters, { type FilterDef } from '@/components/tables/DataTableFilters.vue';
import type { MemberFilterOptions } from '@/types/member';
import { computed } from 'vue';

interface Props {
    modelValue: Record<string, string>;
    options: MemberFilterOptions;
}
const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: Record<string, string>] }>();

const filters = computed<FilterDef[]>(() => [
    { key: 'zone', label: 'Zones', options: props.options.zones.map((z) => ({ value: z.id, label: z.name })) },
    { key: 'lga', label: 'LGAs', options: props.options.lgas.map((l) => ({ value: l.id, label: l.name })) },
    { key: 'ward', label: 'Wards', options: props.options.wards.map((w) => ({ value: w.id, label: w.name })) },
    { key: 'pu', label: 'Polling units', options: [] },
    { key: 'status', label: 'Status', options: props.options.statuses },
]);
</script>

<template>
    <DataTableFilters :filters="filters" :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" />
</template>
