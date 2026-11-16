<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Lock, Mail, User } from 'lucide-vue-next';
import AuthField from '@/components/auth/AuthField.vue';
import AuthSubmit from '@/components/auth/AuthSubmit.vue';
import { useAuthPanel } from '@/composables/useAuthPanel';

useAuthPanel({
  badge: 'Join the Movement · 2026',
  lines: [{ text: 'Be part of' }, { text: 'positive', accent: true }, { text: 'change.' }],
  text: 'Request access to the PRP Bauchi State digital command center and help drive grassroots accountability across all 20 LGAs.',
  steps: [
    'Enter your full name, email, and a strong password',
    'Submit your registration request for verification',
    'Await approval from your State Administrator',
  ],
});

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () =>
  form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
  <Head title="Request Access – PRP Bauchi State" />

  <div class="mb-5">
    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-600">New Account Registration</p>
    <h1 class="mt-1 text-2xl font-black tracking-tight text-neutral-950">
      Create your account
    </h1>
    <p class="mt-1 text-sm text-neutral-600">
      Provide your details below to request portal access.
    </p>
  </div>

  <form class="flex flex-col gap-3.5" novalidate @submit.prevent="submit">
    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 sm:gap-3">
      <AuthField
        id="first_name"
        v-model="form.first_name"
        label="First name"
        :icon="User"
        :error="form.errors.first_name"
        placeholder="Usman"
        autocomplete="given-name"
        :tabindex="1"
        autofocus
      />
      <AuthField
        id="last_name"
        v-model="form.last_name"
        label="Last name"
        :error="form.errors.last_name"
        placeholder="Mohammed"
        autocomplete="family-name"
        :tabindex="2"
      />
    </div>

    <AuthField
      id="email"
      v-model="form.email"
      label="Email address"
      type="email"
      :icon="Mail"
      :error="form.errors.email"
      placeholder="you@example.com"
      autocomplete="email"
      :tabindex="3"
    />

    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 sm:gap-3">
      <AuthField
        id="password"
        v-model="form.password"
        label="Password"
        type="password"
        :icon="Lock"
        :error="form.errors.password"
        placeholder="Min. 8 characters"
        autocomplete="new-password"
        :tabindex="4"
      />

      <AuthField
        id="password_confirmation"
        v-model="form.password_confirmation"
        label="Confirm password"
        type="password"
        :icon="Lock"
        :error="form.errors.password_confirmation"
        placeholder="Re-enter password"
        autocomplete="new-password"
        :tabindex="5"
      />
    </div>

    <div class="pt-1">
      <AuthSubmit :processing="form.processing" label="Request Access" processing-label="Submitting request…" :tabindex="6" />
    </div>

    <div class="border-t border-neutral-200/80 pt-4 text-center">
      <p class="text-sm text-neutral-600">
        Already have an account?
        <Link
          :href="route('login')"
          :tabindex="7"
          class="ml-1 font-bold text-red-600 transition-colors hover:text-neutral-950 hover:underline underline-offset-4"
        >
          Sign in
        </Link>
      </p>
    </div>
  </form>
</template>