<script setup lang="ts">
import { Loader } from 'lucide-vue-next';
import LocationCascade from '@/components/location/LocationCascade.vue';
import Alert from '@/components/ui/Alert.vue';
import Input from '@/components/ui/form/Input.vue';
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import Select from '@/components/ui/form/Select.vue';
import { useLocationModel } from '@/composables/useLocationModel';

import type { LocationOption } from '@/types/member';
import type { SupervisoryAgentTypeOption } from '@/types/supervisory-agent';

const genderOptions = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
];

interface FormShape {
    first_name: string;
    middle_name: string | null;
    last_name: string;
    email: string;
    password?: string;
    phone: string | null;

    gender: string | null;
    occupation: string | null;
    education_status: string | null;

    application_id: string | null;
    type: string | null;
    agents_supervised: number;

    state_id: number | string | null;
    zone_id: number | string | null;
    lga_id: number | string | null;
    ward_id: number | string | null;
    pu_id: number | string | null;

    errors: Record<string, string>;
    processing: boolean;
}

interface Props {
    form: FormShape;
    submitLabel?: string;
    passwordOptional?: boolean;
    states?: LocationOption[];
    zones?: LocationOption[];
    lgas?: LocationOption[];
    wards?: LocationOption[];
    pus?: LocationOption[];
    types?: SupervisoryAgentTypeOption[];
}
const props = withDefaults(defineProps<Props>(), {
    submitLabel: 'Save',
    passwordOptional: false,
    states: () => [],
    zones: () => [],
    lgas: () => [],
    wards: () => [],
    pus: () => [],
    types: () => [],
});

const location = useLocationModel(() => props.form);

const emit = defineEmits<{ submit: []; cancel: [] }>();
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <Alert v-if="Object.keys(form.errors).length" variant="error" title="Please check the form">
            Some fields need your attention. Review the highlighted fields below before submitting again.
        </Alert>
        <!-- Personal information -->
        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Personal information</legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <Input v-model="form.first_name" label="First name" type="text" placeholder="Enter first name" :error="form.errors.first_name" />
                </div>

                <div>
                    <Input
                        v-model="form.middle_name"
                        label="Middle name"
                        type="text"
                        placeholder="Enter middle name"
                        :error="form.errors.middle_name"
                    />
                </div>

                <div>
                    <Input v-model="form.last_name" label="Last name" type="text" placeholder="Enter last name" :error="form.errors.last_name" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <Input v-model="form.email" label="Email" type="email" placeholder="Enter email address" :error="form.errors.email" />
                </div>

                <div>
                    <Input v-model="form.phone" label="Phone" type="tel" placeholder="Enter phone number" :error="form.errors.phone" />
                </div>
            </div>

            <div v-if="'password' in form" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <Input
                        v-model="form.password"
                        label="Password"
                        type="password"
                        placeholder="Enter password"
                        autocomplete="new-password"
                        :error="form.errors.password"
                        :hint="passwordOptional ? 'Leave blank to keep the current password' : undefined"
                    />
                </div>

                <div>
                    <Input
                        v-model="form.application_id"
                        label="Agent ID"
                        type="text"
                        placeholder="Auto-generated if left blank"
                        :error="form.errors.application_id"
                    />
                </div>
            </div>
        </fieldset>

        <!-- Supervision details -->
        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Supervision details</legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <SearchSelect
                        v-model="form.type"
                        :options="types"
                        label="Supervision level"
                        placeholder="Select supervision level"
                        search-placeholder="Search supervision level…"
                        :error="form.errors.type"
                    />
                </div>

                <div>
                    <Input
                        v-model.number="form.agents_supervised"
                        label="Agents supervised"
                        type="number"
                        placeholder="Enter a number"
                        min="0"
                        :error="form.errors.agents_supervised"
                    />
                </div>

                <div>
                    <Select v-model="form.gender" label="Gender" placeholder="Select gender" :options="genderOptions" :error="form.errors.gender" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <Input v-model="form.occupation" label="Occupation" type="text" placeholder="Enter occupation" :error="form.errors.occupation" />
                </div>

                <div>
                    <Input
                        v-model="form.education_status"
                        label="Education status"
                        type="text"
                        placeholder="Enter education status"
                        :error="form.errors.education_status"
                    />
                </div>
            </div>
        </fieldset>

        <!-- Jurisdiction -->
        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Jurisdiction</legend>
            <LocationCascade v-model="location" up-to="pu" :errors="form.errors" />
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
