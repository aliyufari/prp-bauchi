<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/form/Input.vue'

const form = useForm({ current_password: '', password: '', password_confirmation: '' })

function submit() {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: (errors) => {
      if (errors.password) form.reset('password', 'password_confirmation')
      if (errors.current_password) form.reset('current_password')
    },
  })
}
</script>

<template>
  <Head title="Password settings" />

  <section class="w-full min-w-0 space-y-6">
    <header>
      <h1 class="text-xl font-black text-gray-900 sm:text-2xl">Password settings</h1>
      <p class="mt-1 text-sm text-gray-500">Use a long, random password to keep your account secure.</p>
    </header>

    <form class="w-full space-y-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7 lg:p-8" @submit.prevent="submit">
      <div class="grid gap-4 sm:grid-cols-2">
        <!-- Input forwards class/attrs to the <input>, so span the column on a wrapper -->
        <div class="sm:col-span-2">
          <Input
            id="current_password"
            v-model="form.current_password"
            type="password"
            label="Current password"
            autocomplete="current-password"
            required
            :error="form.errors.current_password"
          />
        </div>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          label="New password"
          placeholder="Min. 8 characters"
          autocomplete="new-password"
          required
          :error="form.errors.password"
        />
        <Input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          label="Confirm new password"
          autocomplete="new-password"
          required
          :error="form.errors.password_confirmation"
        />
      </div>

      <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 pt-5">
        <Button type="submit" :loading="form.processing">
          {{ form.processing ? 'Saving…' : 'Save password' }}
        </Button>
        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
        </Transition>
      </div>
    </form>
  </section>
</template>
