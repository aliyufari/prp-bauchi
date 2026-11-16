<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Lock, Mail } from 'lucide-vue-next';
import AuthField from '@/components/auth/AuthField.vue';
import AuthSubmit from '@/components/auth/AuthSubmit.vue';
import { useAuthPanel } from '@/composables/useAuthPanel';

const props = defineProps<{ token: string; email: string }>();

useAuthPanel({
  badge: 'Account Security',
  lines: [{ text: 'Choose a' }, { text: 'new', accent: true }, { text: 'password.' }],
  text: 'Create a strong, unique password to protect your portal credentials and state operational data.',
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () =>
  form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
  <Head title="Reset Password – PRP Bauchi State" />

  <div class="mb-7">
    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-600">Credential Update</p>
    <h1 class="mt-1.5 text-2xl font-black tracking-tight text-neutral-950 sm:text-[1.75rem]">
      Set a new password
    </h1>
    <p class="mt-1.5 text-sm leading-relaxed text-neutral-600">
      Updating credentials for <span class="font-semibold text-neutral-900">{{ email }}</span>
    </p>
  </div>

  <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
    <AuthField
      id="email"
      v-model="form.email"
      label="Email address"
      type="email"
      :icon="Mail"
      :error="form.errors.email"
      autocomplete="email"
      readonly
    />

    <AuthField
      id="password"
      v-model="form.password"
      label="New password"
      type="password"
      :icon="Lock"
      :error="form.errors.password"
      placeholder="Min. 8 characters"
      autocomplete="new-password"
      :tabindex="1"
      autofocus
    />

    <AuthField
      id="password_confirmation"
      v-model="form.password_confirmation"
      label="Confirm new password"
      type="password"
      :icon="Lock"
      :error="form.errors.password_confirmation"
      placeholder="Re-enter password"
      autocomplete="new-password"
      :tabindex="2"
    />

    <AuthSubmit :processing="form.processing" label="Reset Password" processing-label="Updating password…" :tabindex="3" />

    <div class="border-t border-neutral-200/80 pt-5 text-center">
      <Link
        :href="route('login')"
        :tabindex="4"
        class="inline-flex items-center justify-center gap-2 text-sm font-bold text-neutral-700 transition-colors hover:text-red-600"
      >
        <ArrowLeft class="h-4 w-4" />
        <span>Back to sign in</span>
      </Link>
    </div>
  </form>
</template>