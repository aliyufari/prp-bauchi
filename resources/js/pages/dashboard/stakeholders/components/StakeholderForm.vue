<script setup lang="ts">
import FormActions from '@/components/forms/FormActions.vue';
import LocationSelect from '@/components/location/LocationSelect.vue';
import Alert from '@/components/ui/Alert.vue';
import Input from '@/components/ui/form/Input.vue';
import AvatarUpload from '@/components/ui/form/AvatarUpload.vue'
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import Select from '@/components/ui/form/Select.vue';
import { levelFromType } from '@/lib/location';
import { useLocationModel } from '@/composables/useLocationModel';
import { useEducationStatuses } from '@/composables/useEducationStatuses';

import type { StakeholderTypeOption } from '@/types/stakeholder';

const genderOptions = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
];

interface FormShape {
    first_name: string;
    avatar: File | null;
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
    organization: string | null;
    designation: string | null;

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
    /** Existing photo shown in edit mode. */
    avatarUrl?: string | null;
    passwordOptional?: boolean;
    types?: StakeholderTypeOption[];
}
const props = withDefaults(defineProps<Props>(), {
    submitLabel: 'Save',
    passwordOptional: false,
    types: () => [],
});

const location = useLocationModel(() => props.form);
const educationStatuses = useEducationStatuses();

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
            <AvatarUpload v-model="form.avatar" :current-url="avatarUrl" :error="form.errors.avatar" />

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
                        label="Stakeholder ID"
                        type="text"
                        placeholder="Auto-generated if left blank"
                        :error="form.errors.application_id"
                    />
                </div>
            </div>
        </fieldset>

        <!-- Stakeholder details -->
        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Stakeholder details</legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <SearchSelect
                        v-model="form.type"
                        :options="types"
                        label="Category"
                        placeholder="Select stakeholder category"
                        search-placeholder="Search categories…"
                        :error="form.errors.type"
                    />
                </div>

                <div>
                    <Select v-model="form.gender" label="Gender" placeholder="Select gender" :options="genderOptions" :error="form.errors.gender" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <Input
                        v-model="form.organization"
                        label="Organization"
                        type="text"
                        placeholder="e.g. Emirate Council, NGO name"
                        :error="form.errors.organization"
                    />
                </div>

                <div>
                    <Input
                        v-model="form.designation"
                        label="Designation"
                        type="text"
                        placeholder="e.g. District Head, Chairman"
                        :error="form.errors.designation"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <Input v-model="form.occupation" label="Occupation" type="text" placeholder="Enter occupation" :error="form.errors.occupation" />
                </div>

                <div class="sm:col-span-full">
                    <Select
                        v-model="form.education_status"
                        label="Education status"
                        placeholder="Select education status"
                        :options="educationStatuses"
                        :error="form.errors.education_status"
                    />
                </div>
            </div>
        </fieldset>

        <!-- Location -->
        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Location</legend>
            <LocationSelect v-model="location" :level="levelFromType(form.type, types)" :errors="form.errors" />
        </fieldset>

        <FormActions :processing="form.processing" :submit-label="submitLabel" @cancel="emit('cancel')" />
    </form>
</template>
