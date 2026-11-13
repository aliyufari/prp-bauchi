<script setup lang="ts">
import FormActions from '@/components/forms/FormActions.vue';
import Alert from '@/components/ui/Alert.vue'
import Input from '@/components/ui/form/Input.vue'
import AvatarUpload from '@/components/ui/form/AvatarUpload.vue'
import Select from '@/components/ui/form/Select.vue'
import Checkbox from '@/components/ui/form/Checkbox.vue'
import LocationCascade from '@/components/location/LocationCascade.vue'
import { useLocationModel } from '@/composables/useLocationModel'
import type { EducationStatusOption, FormShape } from '@/types/member'

interface Props {
  form: FormShape
  submitLabel?: string
  /** Existing photo shown in edit mode. */
  avatarUrl?: string | null
  passwordOptional?: boolean
  educationStatuses?: EducationStatusOption[]
}
const props = withDefaults(defineProps<Props>(), {
  submitLabel: 'Save', passwordOptional: false, educationStatuses: () => [],
})
const location = useLocationModel(() => props.form)
const genderOptions = [{ value: 'male', label: 'Male' }, { value: 'female', label: 'Female' }]
const emit = defineEmits<{ submit: []; cancel: [] }>()
</script>

<template>
  <form class="space-y-6" @submit.prevent="emit('submit')">
    <Alert v-if="Object.keys(form.errors).length" variant="error" title="Please check the form">
      Some fields need your attention. Review the highlighted fields below before submitting again.
    </Alert>
    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">Personal information</legend>
            <AvatarUpload v-model="form.avatar" :current-url="avatarUrl" :error="form.errors.avatar" />
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <Input v-model="form.first_name" label="First name" placeholder="Enter first name" :error="form.errors.first_name" />
        <Input v-model="form.middle_name" label="Middle name" placeholder="Enter middle name" :error="form.errors.middle_name" />
        <Input v-model="form.last_name" label="Last name" placeholder="Enter last name" :error="form.errors.last_name" />
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Input v-model="form.email" label="Email" type="email" placeholder="Enter email address" :error="form.errors.email" />
        <Input v-model="form.phone" label="Phone" type="tel" placeholder="Enter phone number" :error="form.errors.phone" />
      </div>
      <div v-if="'password' in form" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Input v-model="form.password" label="Password" type="password" autocomplete="new-password"
          placeholder="Enter password" :hint="passwordOptional ? 'Leave blank to keep current password' : undefined"
          :error="form.errors.password" />
        <Input v-model="form.application_id" label="Membership ID" placeholder="Auto-generated if left blank" :error="form.errors.application_id" />
      </div>
    </fieldset>
    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">Additional information</legend>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <Select v-model="form.gender" label="Gender" placeholder="Select gender" :options="genderOptions" :error="form.errors.gender" />
        <Input v-model="form.occupation" label="Occupation" placeholder="Enter occupation" :error="form.errors.occupation" />
        <div class="sm:col-span-full">
          <Select v-model="form.education_status" label="Education status" placeholder="Select education status"
          :options="educationStatuses" :error="form.errors.education_status" />
        </div>
      </div>
    </fieldset>
    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">Location</legend>
      <LocationCascade v-model="location" up-to="pu" :errors="form.errors" />
    </fieldset>
    <fieldset class="space-y-4">
      <legend class="text-xs font-bold uppercase tracking-wide text-gray-400">Mentorship &amp; training</legend>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Input v-model="form.mentor_name" label="Mentor name" placeholder="Enter mentor name" :error="form.errors.mentor_name" />
        <div class="flex flex-wrap items-center gap-6 pt-7">
          <Checkbox v-model="form.mentor_status" label="Has a mentor" />
          <Checkbox v-model="form.training_status" label="Training completed" />
        </div>
      </div>
    </fieldset>
    <FormActions :processing="form.processing" :submit-label="submitLabel" @cancel="emit('cancel')" />
  </form>
</template>
