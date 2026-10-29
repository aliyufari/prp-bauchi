<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { KeyRound, Lock } from 'lucide-vue-next'
import AuthField from '@/components/auth/AuthField.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'

const form = useForm({ current_password: '', password: '', password_confirmation: '' })

const submit = () =>
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: (errors) => {
      if (errors.password) form.reset('password', 'password_confirmation')
      if (errors.current_password) form.reset('current_password')
    },
  })
</script>

<template>
  <Head title="Update Password" />

  <div class="mx-auto w-full max-w-xl space-y-6">
    <div>
      <h2 class="text-lg font-black text-gray-900 sm:text-xl">Update password</h2>
      <p class="mt-1 text-xs font-medium text-gray-500 sm:text-sm">Use a long, random password to keep your account secure.</p>
    </div>

    <form class="space-y-5 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-6" novalidate @submit.prevent="submit">
      <AuthField id="current_password" v-model="form.current_password" label="Current password" type="password" :icon="KeyRound" :error="form.errors.current_password" autocomplete="current-password" autofocus />
      <AuthField id="password" v-model="form.password" label="New password" type="password" :icon="Lock" :error="form.errors.password" placeholder="Min. 8 characters" autocomplete="new-password" />
      <AuthField id="password_confirmation" v-model="form.password_confirmation" label="Confirm new password" type="password" :icon="Lock" :error="form.errors.password_confirmation" autocomplete="new-password" />

      <div class="flex items-center gap-4">
        <div class="w-40"><AuthSubmit :processing="form.processing" label="Save password" processing-label="Saving…" /></div>
        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
          <p v-show="form.recentlySuccessful" class="text-sm font-medium text-green-700">Saved.</p>
        </Transition>
      </div>
    </form>
  </div>
</template>