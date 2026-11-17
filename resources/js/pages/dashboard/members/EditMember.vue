<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import SlideOver from '@/components/ui/SlideOver.vue'
import MemberForm from './components/MemberForm.vue'
import {
    emptyMemberFormValues,
    memberToFormValues,
} from '@/composables/members/useMember'
import type { Member, FormOptions } from '@/types/member'

interface Props {
    member: Member | null
    formOptions: FormOptions
}

const props = defineProps<Props>()

const emit = defineEmits<{
    close: []
}>()

const form = useForm(emptyMemberFormValues())

// Re-seed whenever a new member is opened for editing.
watch(
    () => props.member,
    (member) => {
        if (!member) return

        form.clearErrors()

        const values = memberToFormValues(member)

        form.defaults(values)
        form.reset()
        Object.assign(form, values)
    },
    { immediate: true },
)

function submit() {
    if (!props.member) return

    form.transform((data) => ({ ...data, _method: 'patch' })).post(route('members.update', props.member.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => emit('close'),
    })
}

function cancel() {
    form.clearErrors()
    emit('close')
}
</script>

<template>
    <SlideOver
        :open="member !== null"
        title="Edit Member"
        :description="
            member
                ? `Update details for ${member.first_name} ${member.last_name}.`
                : ''
        "
        size="lg"
        side="right"
        @close="cancel"
    >
        <MemberForm
            v-if="member"
            :form="form"
            submit-label="Save changes"
            :password-optional="true"
            :avatar-url="member.avatar_url"
            :education-statuses="formOptions.educationStatuses"
            @submit="submit"
            @cancel="cancel"
        />
    </SlideOver>
</template>