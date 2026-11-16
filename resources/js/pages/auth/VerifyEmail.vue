<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LogOut, MailCheck } from 'lucide-vue-next';
import AuthNotice from '@/components/auth/AuthNotice.vue';
import AuthSubmit from '@/components/auth/AuthSubmit.vue';
import { useAuthPanel } from '@/composables/useAuthPanel';

defineProps<{
  status?: string;
}>();

useAuthPanel({
  badge: 'Email Verification',
  lines: [{ text: 'Verify your' }, { text: 'email', accent: true }, { text: 'address.' }],
  text: 'Confirming your email ensures you receive official party alerts, coordinator notices, and security updates.',
});

const form = useForm({});

const submit = () => {
  form.post(route('verification.send'));
};
</script>

<template>
  <Head title="Verify Email – PRP Bauchi State" />

  <div class="mb-7">
    <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-600">
      <MailCheck class="h-5 w-5" />
    </div>
    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-600">Step 1 · Account Activation</p>
    <h1 class="mt-1.5 text-2xl font-black tracking-tight text-neutral-950 sm:text-[1.75rem]">
      Check your inbox
    </h1>
    <p class="mt-1.5 text-sm leading-relaxed text-neutral-600">
      We sent a verification link to your email address. Click the link inside to activate your portal access.
    </p>
  </div>

  <AuthNotice v-if="status === 'verification-link-sent'" class="mb-5">
    A fresh verification link has been sent to the email address you provided during registration.
  </AuthNotice>

  <form class="flex flex-col gap-5" @submit.prevent="submit">
    <AuthSubmit
      :processing="form.processing"
      label="Resend Verification Email"
      processing-label="Sending email…"
      :tabindex="1"
    />

    <div class="border-t border-neutral-200/80 pt-5 text-center">
      <Link
        :href="route('logout')"
        method="post"
        as="button"
        :tabindex="2"
        class="inline-flex items-center justify-center gap-2 text-sm font-bold text-neutral-600 transition-colors hover:text-red-600"
      >
        <LogOut class="h-4 w-4" />
        <span>Sign out</span>
      </Link>
    </div>
  </form>
</template>