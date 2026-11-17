<script setup lang="ts">
import DataTableFilters, { type FilterDef } from '@/components/tables/DataTableFilters.vue';
import type { PartyAgentFilterOptions } from '@/types/party-agent';
import { computed } from 'vue';

interface Props {
    modelValue: Record<string, string>;
    options: PartyAgentFilterOptions;
}
const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: Record<string, string>] }>();

const filters = computed<FilterDef[]>(() => [
    { key: 'ward', label: 'Wards', options: props.options.wards.map((w) => ({ value: w.id, label: w.name })) },
    { key: 'pu', label: 'Polling units', options: [] },
    { key: 'type', label: 'Type', options: props.options.types },
    { key: 'status', label: 'Status', options: props.options.statuses },
]);
</script>

<template>
    <DataTableFilters :filters="filters" :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)" />
</template>
