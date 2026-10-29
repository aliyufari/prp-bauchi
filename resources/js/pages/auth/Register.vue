<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Lock, Mail, User } from 'lucide-vue-next'
import AuthField from '@/components/auth/AuthField.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'
import { useAuthPanel } from '@/composables/useAuthPanel'

useAuthPanel({
  badge: 'Join the Movement',
  lines: [{ text: 'Be part of' }, { text: 'positive', accent: true }, { text: 'change.' }],
  text: 'Register to gain access to the PRP Bauchi State Management System and contribute to a united, peaceful and prosperous Bauchi State.',
  steps: [
    'Fill in your name, email and a secure password',
    'Submit your registration request',
    'Await approval from the State Administrator',
  ],
})

const form = useForm({ first_name: '', last_name: '', email: '', password: '', password_confirmation: '' })

const submit = () =>
  form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
  <Head title="Request Access – PRP Bauchi State" />

  <div class="mb-6 sm:mb-8">
    <h2 class="text-[#111827] font-black text-xl sm:text-2xl tracking-tight">Create your account</h2>
    <p class="text-[#6b7280] text-sm mt-1">Fill in your details to request system access</p>
  </div>

  <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
    <div class="grid grid-cols-2 gap-3">
      <AuthField id="first_name" v-model="form.first_name" label="First name" :icon="User" :error="form.errors.first_name" placeholder="Usman" autocomplete="given-name" :tabindex="1" autofocus />
      <AuthField id="last_name" v-model="form.last_name" label="Last name" :error="form.errors.last_name" placeholder="Mohammed" autocomplete="family-name" :tabindex="2" />
    </div>

    <AuthField id="email" v-model="form.email" label="Email address" type="email" :icon="Mail" :error="form.errors.email" placeholder="you@example.com" autocomplete="email" :tabindex="3" />
    <AuthField id="password" v-model="form.password" label="Password" type="password" :icon="Lock" :error="form.errors.password" placeholder="Min. 8 characters" autocomplete="new-password" :tabindex="4" />
    <AuthField id="password_confirmation" v-model="form.password_confirmation" label="Confirm password" type="password" :icon="Lock" :error="form.errors.password_confirmation" placeholder="Re-enter password" autocomplete="new-password" :tabindex="5" />

    <AuthSubmit :processing="form.processing" label="Create account" processing-label="Creating account…" :tabindex="6" />

    <p class="mt-1 text-center text-sm text-[#6b7280]">
      Already have an account?
      <Link :href="route('login')" :tabindex="7" class="ml-1 font-semibold text-[#16a34a] hover:text-[#0d3b26] underline-offset-2 hover:underline transition-colors">Sign in</Link>
    </p>
  </form>
</template>