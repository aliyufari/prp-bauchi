<script setup lang="ts">
import { Loader } from 'lucide-vue-next';
import Alert from '@/components/ui/Alert.vue';
import Input from '@/components/ui/form/Input.vue';
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import Select from '@/components/ui/form/Select.vue';
import Textarea from '@/components/ui/form/Textarea.vue';
import type { AspirantOfficeOption, ConstituencyOption } from '@/types/aspirant';
import { computed } from 'vue';

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
    education_status: string | null;

    office: string | null;
    constituency_id: string | null;
    title: string | null;
    vision: string | null;
    mission: string | null;
    image_url: string | null;

    overall_progress: number;
    total_supporters: number;
    supporters_growth_this_week: number;
    campaign_team_members: number;

    errors: Record<string, string>;
    processing: boolean;
}

interface Props {
    form: FormShape;
    submitLabel?: string;
    passwordOptional?: boolean;
    lockOffice?: boolean;
    constituencies?: ConstituencyOption[];
    offices?: AspirantOfficeOption[];
}
const props = withDefaults(defineProps<Props>(), {
    submitLabel: 'Save',
    passwordOptional: false,
    lockOffice: false,
    constituencies: () => [],
    offices: () => [],
});
const constituencyOptions = computed(() => props.constituencies.map((item) => ({ value: item.id, label: item.name })));

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
                    <Select v-model="form.gender" label="Gender" placeholder="Select gender" :options="genderOptions" :error="form.errors.gender" />
                </div>
            </div>
        </fieldset>

        <!-- Candidacy -->
        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Candidacy</legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <SearchSelect
                        v-model="form.office"
                        :options="offices"
                        label="Office"
                        placeholder="Select office"
                        search-placeholder="Search office…"
                        :disabled="lockOffice"
                        :error="form.errors.office"
                    />
                </div>

                <div>
                    <SearchSelect
                        v-model="form.constituency_id"
                        :options="constituencyOptions"
                        label="Constituency"
                        placeholder="Select constituency"
                        search-placeholder="Search constituencies…"
                        :error="form.errors.constituency_id"
                    />
                </div>
            </div>

            <div>
                <Input
                    v-model="form.title"
                    label="Title"
                    type="text"
                    placeholder="e.g. Governorship Candidate, Bauchi State"
                    :error="form.errors.title"
                />
            </div>

            <div>
                <Input
                    v-model="form.image_url"
                    label="Photo URL"
                    type="text"
                    placeholder="https://... (leave blank to use default image)"
                    :error="form.errors.image_url"
                />
            </div>
        </fieldset>

        <!-- Campaign profile — only meaningful for the governorship candidate -->
        <fieldset v-if="form.office === 'governor'" class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Campaign profile</legend>

            <div>
                <Textarea v-model="form.vision" label="Vision" :rows="3" placeholder="Enter vision" :error="form.errors.vision" />
            </div>

            <div>
                <Textarea v-model="form.mission" label="Mission" :rows="3" placeholder="Enter mission" :error="form.errors.mission" />
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <Input
                        v-model.number="form.overall_progress"
                        label="Progress (%)"
                        type="number"
                        placeholder="Enter a number"
                        min="0"
                        max="100"
                        :error="form.errors.overall_progress"
                    />
                </div>
                <div>
                    <Input
                        v-model.number="form.total_supporters"
                        label="Supporters"
                        type="number"
                        placeholder="Enter a number"
                        min="0"
                        :error="form.errors.total_supporters"
                    />
                </div>
                <div>
                    <Input
                        v-model.number="form.supporters_growth_this_week"
                        label="Growth this week"
                        type="number"
                        placeholder="Enter a number"
                        :error="form.errors.supporters_growth_this_week"
                    />
                </div>
                <div>
                    <Input
                        v-model.number="form.campaign_team_members"
                        label="Team members"
                        type="number"
                        placeholder="Enter a number"
                        min="0"
                        :error="form.errors.campaign_team_members"
                    />
                </div>
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
