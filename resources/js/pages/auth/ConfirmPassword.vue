<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Lock } from 'lucide-vue-next';
import AuthField from '@/components/auth/AuthField.vue';
import AuthSubmit from '@/components/auth/AuthSubmit.vue';
import { useAuthPanel } from '@/composables/useAuthPanel';

useAuthPanel({
  badge: 'Security Verification',
  lines: [{ text: 'Confirm' }, { text: 'your', accent: true }, { text: 'identity.' }],
  text: 'You are accessing a protected area of the PRP Bauchi State portal. Please verify your password to continue.',
});

const form = useForm({
  password: '',
});

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => form.reset(),
  });
};
</script>

<template>
  <Head title="Confirm Password – PRP Bauchi State" />

  <div class="mb-7">
    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-600">Protected Area</p>
    <h1 class="mt-1.5 text-2xl font-black tracking-tight text-neutral-950 sm:text-[1.75rem]">
      Confirm your password
    </h1>
    <p class="mt-1.5 text-sm leading-relaxed text-neutral-600">
      Please confirm your password before continuing.
    </p>
  </div>

  <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
    <AuthField
      id="password"
      v-model="form.password"
      label="Current password"
      type="password"
      :icon="Lock"
      :error="form.errors.password"
      placeholder="••••••••"
      autocomplete="current-password"
      :tabindex="1"
      autofocus
    />

    <AuthSubmit
      :processing="form.processing"
      label="Confirm Password"
      processing-label="Verifying…"
      :tabindex="2"
    />
  </form>
</template>