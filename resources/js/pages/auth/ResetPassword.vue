<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Lock, Mail } from 'lucide-vue-next'
import AuthField from '@/components/auth/AuthField.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'
import { useAuthPanel } from '@/composables/useAuthPanel'

const props = defineProps<{ token: string; email: string }>()

useAuthPanel({
  badge: 'Secure Your Account',
  lines: [{ text: 'Choose a' }, { text: 'new', accent: true }, { text: 'password.' }],
  text: "Pick something strong that you don't use anywhere else.",
})

const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' })

const submit = () =>
  form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
  <Head title="Reset Password – PRP Bauchi State" />

  <div class="mb-6 sm:mb-8">
    <h2 class="text-[#111827] font-black text-xl sm:text-2xl tracking-tight">Set a new password</h2>
    <p class="text-[#6b7280] text-sm mt-1">For {{ email }}</p>
  </div>

  <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
    <AuthField id="email" v-model="form.email" label="Email address" type="email" :icon="Mail" :error="form.errors.email" autocomplete="email" readonly />
    <AuthField id="password" v-model="form.password" label="New password" type="password" :icon="Lock" :error="form.errors.password" placeholder="Min. 8 characters" autocomplete="new-password" :tabindex="1" autofocus />
    <AuthField id="password_confirmation" v-model="form.password_confirmation" label="Confirm new password" type="password" :icon="Lock" :error="form.errors.password_confirmation" placeholder="Re-enter password" autocomplete="new-password" :tabindex="2" />

    <AuthSubmit :processing="form.processing" label="Reset password" processing-label="Resetting…" :tabindex="3" />
  </form>
</template>