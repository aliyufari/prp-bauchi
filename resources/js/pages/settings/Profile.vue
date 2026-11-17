<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { Camera, UserRound, X } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/form/Input.vue'

interface ProfileUser {
  first_name: string
  middle_name: string | null
  last_name: string
  email: string
  avatar: string | null
  last_login_at: string | null
  last_login_ip: string | null
  last_login_browser: string | null
  last_login_device: string | null
}

const props = defineProps<{ user: ProfileUser; status?: string | null }>()
const page = usePage()
const form = useForm({
  first_name: props.user.first_name ?? '',
  middle_name: props.user.middle_name ?? '',
  last_name: props.user.last_name ?? '',
  email: props.user.email ?? '',
  avatar: null as File | null,
  remove_avatar: false,
})
const selectedPreview = ref<string | null>(null)
const currentAvatar = computed(() => (page.props as any).auth?.user?.avatar_url ?? null)
const avatarPreview = computed(() => selectedPreview.value ?? (form.remove_avatar ? null : currentAvatar.value))

watch(() => form.avatar, (file) => {
  if (selectedPreview.value) URL.revokeObjectURL(selectedPreview.value)
  selectedPreview.value = file ? URL.createObjectURL(file) : null
  if (file) form.remove_avatar = false
})
onBeforeUnmount(() => {
  if (selectedPreview.value) URL.revokeObjectURL(selectedPreview.value)
})

function chooseAvatar(event: Event) {
  const input = event.target as HTMLInputElement
  form.avatar = input.files?.[0] ?? null
}

function removeAvatar() {
  form.avatar = null
  form.remove_avatar = true
  selectedPreview.value && URL.revokeObjectURL(selectedPreview.value)
  selectedPreview.value = null
}

function submit() {
  form.transform((data) => ({ ...data, _method: 'patch' })).post(route('profile.update'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.avatar = null
      form.remove_avatar = false
    },
  })
}
</script>

<template>
  <Head title="Profile settings" />

  <section class="w-full min-w-0 space-y-6">
    <header>
      <h1 class="text-xl font-black text-gray-900 sm:text-2xl">Profile settings</h1>
      <p class="mt-1 text-sm text-gray-500">Update your personal information and profile picture.</p>
    </header>

    <form class="w-full space-y-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7 lg:p-8" @submit.prevent="submit">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200">
          <img v-if="avatarPreview" :src="avatarPreview" alt="Profile avatar preview" class="h-full w-full object-cover" />
          <UserRound v-else class="m-auto h-full w-full p-5 text-gray-400" aria-hidden="true" />
        </div>
        <div class="space-y-2">
          <div class="flex flex-wrap items-center gap-2">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
              <Camera class="h-4 w-4" />
              Choose photo
              <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="chooseAvatar" />
            </label>
            <button v-if="currentAvatar || form.avatar" type="button" class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" @click="removeAvatar">
              <X class="h-4 w-4" /> Remove
            </button>
          </div>
          <p class="text-xs text-gray-500">JPG, PNG, or WebP. Maximum file size: 2 MB.</p>
          <p v-if="form.errors.avatar" class="text-xs text-red-600">{{ form.errors.avatar }}</p>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <Input id="first_name" v-model="form.first_name" label="First name" autocomplete="given-name" required :error="form.errors.first_name" />
        <Input id="middle_name" v-model="form.middle_name" label="Middle name (optional)" autocomplete="additional-name" :error="form.errors.middle_name" />
        <Input id="last_name" v-model="form.last_name" label="Last name" autocomplete="family-name" required :error="form.errors.last_name" />
        <Input id="email" v-model="form.email" type="email" label="Email address" autocomplete="email" required :error="form.errors.email" />
      </div>

      <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 pt-5">
        <Button type="submit" :loading="form.processing">
          {{ form.processing ? 'Saving…' : 'Save changes' }}
        </Button>
      </div>
    </form>

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7">
      <h2 class="text-sm font-black text-gray-900">Recent account access</h2>
      <p class="mt-1 text-xs text-gray-500">Details from your most recent successful sign-in.</p>
      <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div><dt class="text-xs text-gray-400">Last login</dt><dd class="mt-1 text-sm font-semibold text-gray-800">{{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'No login recorded' }}</dd></div>
        <div><dt class="text-xs text-gray-400">IP address</dt><dd class="mt-1 text-sm font-semibold text-gray-800">{{ user.last_login_ip || '—' }}</dd></div>
        <div><dt class="text-xs text-gray-400">Browser</dt><dd class="mt-1 text-sm font-semibold text-gray-800">{{ user.last_login_browser || '—' }}</dd></div>
        <div><dt class="text-xs text-gray-400">Device</dt><dd class="mt-1 text-sm font-semibold text-gray-800">{{ user.last_login_device || '—' }}</dd></div>
      </dl>
    </section>
  </section>
</template>
