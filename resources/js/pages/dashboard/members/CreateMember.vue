<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import SlideOver from '@/components/ui/SlideOver.vue'
import MemberForm from './components/MemberForm.vue'
import { emptyMemberFormValues } from '@/composables/members/useMember'
import type { FormOptions } from '@/types/member'

interface Props {
    open: boolean
    formOptions: FormOptions
}

defineProps<Props>()

const emit = defineEmits<{
    close: []
}>()

const form = useForm(emptyMemberFormValues())

function submit() {
    form.transform((data) => data).post(route('members.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            emit('close')
        },
    })
}

function cancel() {
    form.clearErrors()
    form.reset()
    emit('close')
}
</script>

<template>
    <SlideOver
        :open="open"
        title="Add Member"
        description="Register a new member to the party database."
        size="lg"
        side="right"
        @close="cancel"
    >
        <MemberForm
            :form="form"
            submit-label="Create member"
            :password-optional="false"
            :education-statuses="formOptions.educationStatuses"
            @submit="submit"
            @cancel="cancel"
        />
    </SlideOver>
</template>