<script setup lang="ts">
import { Loader } from 'lucide-vue-next';
import Alert from '@/components/ui/Alert.vue';
import Checkbox from '@/components/ui/form/Checkbox.vue';
import Input from '@/components/ui/form/Input.vue';
import SearchSelect from '@/components/ui/form/SearchSelect.vue';
import Textarea from '@/components/ui/form/Textarea.vue';
import type { AspirantOption, UserOption } from '@/types/campaign-team';
import { computed } from 'vue';

interface FormShape {
    aspirant_id: string | null;
    name: string;
    description: string | null;
    team_lead_id: string | null;
    is_active: boolean;
    errors: Record<string, string>;
    processing: boolean;
}

interface Props {
    form: FormShape;
    submitLabel?: string;
    aspirants?: AspirantOption[];
    potentialLeads?: UserOption[];
}
const props = withDefaults(defineProps<Props>(), { submitLabel: 'Save', aspirants: () => [], potentialLeads: () => [] });
const aspirantOptions = computed(() =>
    props.aspirants.map((item) => ({
        value: item.id,
        label: item.name,
        description: item.office ?? undefined,
    })),
);
const leadOptions = computed(() => props.potentialLeads.map((item) => ({ value: item.id, label: item.name })));
const emit = defineEmits<{ submit: []; cancel: [] }>();
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <Alert v-if="Object.keys(form.errors).length" variant="error" title="Please check the form">
            Some fields need your attention. Review the highlighted fields below before submitting again.
        </Alert>
        <fieldset class="space-y-4">
            <div v-if="'aspirant_id' in form">
                <SearchSelect
                    v-model="form.aspirant_id"
                    :options="aspirantOptions"
                    label="Campaign (Aspirant)"
                    placeholder="Select aspirant"
                    search-placeholder="Search campaigns…"
                    :error="form.errors.aspirant_id"
                />
            </div>

            <div>
                <Input v-model="form.name" label="Team name" type="text" placeholder="e.g. Ground Operations Team" :error="form.errors.name" />
            </div>

            <div>
                <Textarea
                    v-model="form.description"
                    label="Description"
                    :rows="2"
                    placeholder="Describe the team and its responsibilities"
                    :error="form.errors.description"
                />
            </div>

            <div>
                <SearchSelect
                    v-model="form.team_lead_id"
                    :options="leadOptions"
                    label="Team lead"
                    placeholder="Unassigned"
                    search-placeholder="Search team leads…"
                />
                <p class="mt-1 text-xs text-gray-400">The team lead can add/remove their own team's members.</p>
            </div>

            <Checkbox v-model="form.is_active" label="Team is active" />
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
