<script setup lang="ts">
import { Loader } from 'lucide-vue-next';
import Alert from '@/components/ui/Alert.vue';
import Input from '@/components/ui/form/Input.vue';
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import type { WardOption } from '@/types/polling-unit';
import { computed } from 'vue';

interface FormShape {
    name: string;
    code: string | null;
    ward_id: string | null;
    address: string | null;
    registered_voters: number;

    errors: Record<string, string>;
    processing: boolean;
}

interface Props {
    form: FormShape;
    submitLabel?: string;
    wards?: WardOption[];
}
const props = withDefaults(defineProps<Props>(), {
    submitLabel: 'Save',
    wards: () => [],
});

const emit = defineEmits<{ submit: []; cancel: [] }>();
const wardOptions = computed(() => props.wards.map((ward) => ({ value: ward.id, label: ward.name })));
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <Alert v-if="Object.keys(form.errors).length" variant="error" title="Please check the form">
            Some fields need your attention. Review the highlighted fields below before submitting again.
        </Alert>
        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Polling unit details</legend>

            <div>
                <Input v-model="form.name" label="Name" type="text" placeholder="e.g. Ward 1 Primary School Hall A" :error="form.errors.name" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <Input v-model="form.code" label="Code" type="text" placeholder="e.g. PU/001/BA" :error="form.errors.code" />
                </div>

                <div class="sm:col-span-2">
                    <SearchSelect
                        v-model="form.ward_id"
                        :options="wardOptions"
                        label="Ward"
                        placeholder="Select ward"
                        search-placeholder="Search wards…"
                        :error="form.errors.ward_id"
                    />
                </div>
            </div>

            <div>
                <Input v-model="form.address" label="Address" type="text" placeholder="Enter address" :error="form.errors.address" />
            </div>

            <div>
                <Input
                    v-model.number="form.registered_voters"
                    label="Registered voters"
                    type="number"
                    placeholder="Enter a number"
                    min="0"
                    :error="form.errors.registered_voters"
                />
            </div>
        </fieldset>

        <div class="flex items-center justify-end gap-3 pt-2">
            <button
                type="button"
                @click="emit('cancel')"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-gray-500 transition hover:bg-gray-100"
            >
                Cancel
            </button>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-xl bg-green-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 active:bg-green-900 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <Loader v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ form.processing ? 'Saving…' : submitLabel }}
            </button>
        </div>
    </form>
</template>
