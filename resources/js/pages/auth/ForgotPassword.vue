<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Mail } from 'lucide-vue-next';
import AuthField from '@/components/auth/AuthField.vue';
import AuthNotice from '@/components/auth/AuthNotice.vue';
import AuthSubmit from '@/components/auth/AuthSubmit.vue';
import { useAuthPanel } from '@/composables/useAuthPanel';

defineProps<{ status?: string }>();

useAuthPanel({
  badge: 'Account Recovery',
  lines: [{ text: 'Regain' }, { text: 'access', accent: true }, { text: 'in minutes.' }],
  text: "Enter the email address registered to your account and we'll send you a secure password reset link.",
});

const form = useForm({ email: '' });

const submit = () => form.post(route('password.email'));
</script>

<template>
  <Head title="Forgot Password – PRP Bauchi State" />

  <div class="mb-7">
    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-600">Password Recovery</p>
    <h1 class="mt-1.5 text-2xl font-black tracking-tight text-neutral-950 sm:text-[1.75rem]">
      Forgot your password?
    </h1>
    <p class="mt-1.5 text-sm leading-relaxed text-neutral-600">
      We'll email you a secure link to choose a new password.
    </p>
  </div>

  <AuthNotice v-if="status" class="mb-5">{{ status }}</AuthNotice>

  <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
    <AuthField
      id="email"
      v-model="form.email"
      label="Email address"
      type="email"
      :icon="Mail"
      :error="form.errors.email"
      placeholder="you@example.com"
      autocomplete="email"
      :tabindex="1"
      autofocus
    />

    <AuthSubmit :processing="form.processing" label="Send Reset Link" processing-label="Sending link…" :tabindex="2" />

    <div class="border-t border-neutral-200/80 pt-5 text-center">
      <Link
        :href="route('login')"
        :tabindex="3"
        class="inline-flex items-center justify-center gap-2 text-sm font-bold text-neutral-700 transition-colors hover:text-red-600"
      >
        <ArrowLeft class="h-4 w-4" />
        <span>Back to sign in</span>
      </Link>
    </div>
  </form>
</template>