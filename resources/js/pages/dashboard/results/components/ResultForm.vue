<script setup lang="ts">
import { Loader } from 'lucide-vue-next';
import LocationCascade from '@/components/location/LocationCascade.vue';
import Alert from '@/components/ui/Alert.vue';
import DatePicker from '@/components/ui/form/DatePicker.vue';
import Input from '@/components/ui/form/Input.vue';
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import Textarea from '@/components/ui/form/Textarea.vue';
import { useLocationModel } from '@/composables/useLocationModel';
import type { LocationApiOption, LocationLevel } from '@/types/location';
import type { LocationOption, OfficeOption, PartyOption } from '@/types/result';
import { computed } from 'vue';

interface FormShape {
    state_id: string | null;
    zone_id: string | null;
    lga_id: string | null;
    ward_id: string | null;
    pu_id: string | null;
    office: string | null;
    election_date: string | null;
    registered_voters: number;
    accredited_voters: number;
    rejected_votes: number;
    party_votes: Record<string, number>;
    result_sheet_url: string | null;
    remarks: string | null;
    errors: Record<string, string>;
    processing: boolean;
}

interface Props {
    form: FormShape;
    submitLabel?: string;
    offices?: OfficeOption[];
    parties?: PartyOption[];
    states?: LocationOption[];
}
const props = withDefaults(defineProps<Props>(), {
    submitLabel: 'Save',
    offices: () => [],
    parties: () => [],
    states: () => [],
});

const emit = defineEmits<{ submit: []; cancel: [] }>();

const location = useLocationModel(() => props.form);

function handleLocationSelected(payload: { level: LocationLevel; option: LocationApiOption | null }) {
    if (payload.level === 'pu' && payload.option?.registered_voters != null) {
        props.form.registered_voters = Number(payload.option.registered_voters);
    }
}

const totalValidVotes = computed(() => Object.values(props.form.party_votes || {}).reduce((sum, v) => sum + (Number(v) || 0), 0));
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <Alert v-if="Object.keys(form.errors).length" variant="error" title="Please check the form">
            Some fields need your attention. Review the highlighted fields below before submitting again.
        </Alert>
        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Polling unit location</legend>
            <LocationCascade v-model="location" up-to="pu" :errors="form.errors" @selected="handleLocationSelected" />
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Election details</legend>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <SearchSelect
                        v-model="form.office"
                        :options="offices"
                        label="Office"
                        placeholder="Select office"
                        search-placeholder="Search office…"
                        :error="form.errors.office"
                    />
                </div>

                <div>
                    <DatePicker v-model="form.election_date" label="Election date" :error="form.errors.election_date" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                <div>
                    <Input
                        v-model.number="form.accredited_voters"
                        label="Accredited voters"
                        type="number"
                        placeholder="Enter a number"
                        min="0"
                        :error="form.errors.accredited_voters"
                    />
                </div>
                <div>
                    <Input
                        v-model.number="form.rejected_votes"
                        label="Rejected votes"
                        type="number"
                        placeholder="Enter a number"
                        min="0"
                        :error="form.errors.rejected_votes"
                    />
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">
                Votes by party
                <span class="ml-2 font-normal text-gray-400 normal-case">Total valid: {{ totalValidVotes.toLocaleString() }}</span>
            </legend>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div v-for="party in parties" :key="party.value">
                    <Input v-model.number="form.party_votes[party.value]" :label="party.label" type="number" placeholder="Enter votes" min="0" />
                </div>
            </div>
            <p v-if="form.errors.party_votes" class="text-xs text-red-600">{{ form.errors.party_votes }}</p>
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="text-xs font-bold tracking-wide text-gray-400 uppercase">Supporting information</legend>

            <div>
                <Input
                    v-model="form.result_sheet_url"
                    label="Result sheet URL"
                    type="text"
                    placeholder="Link to scanned result sheet (optional)"
                    :error="form.errors.result_sheet_url"
                />
            </div>

            <div>
                <Textarea v-model="form.remarks" label="Remarks" :rows="2" placeholder="Enter remarks" :error="form.errors.remarks" />
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
                class="rounded-xl bg-green-700 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 disabled:opacity-60"
            >
                <Loader v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ form.processing ? 'Saving…' : submitLabel }}
            </button>
        </div>
    </form>
</template>
