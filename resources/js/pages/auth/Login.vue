<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Lock, Mail } from 'lucide-vue-next';
import AuthField from '@/components/auth/AuthField.vue';
import AuthNotice from '@/components/auth/AuthNotice.vue';
import AuthSubmit from '@/components/auth/AuthSubmit.vue';
import Checkbox from '@/components/ui/form/Checkbox.vue';
import { useAuthPanel } from '@/composables/useAuthPanel';

defineProps<{ status?: string; canResetPassword: boolean }>();

useAuthPanel({
  badge: 'Official State Portal · 2026',
  lines: [{ text: 'Justice, Unity' }, { text: 'Progress', accent: true }, { text: 'for Bauchi.' }],
  text: "Sign in to the People's Redemption Party digital command center to manage members, field operations, and verified election data.",
});

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
  <Head title="Sign In – PRP Bauchi State" />

  <div class="mb-7">
    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-600">Member &amp; Coordinator Access</p>
    <h1 class="mt-1.5 text-2xl font-black tracking-tight text-neutral-950 sm:text-[1.75rem]">
      Sign in to your account
    </h1>
    <p class="mt-1.5 text-sm leading-relaxed text-neutral-600">
      Enter your credentials to access the state command dashboard.
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

    <AuthField
      id="password"
      v-model="form.password"
      label="Password"
      type="password"
      :icon="Lock"
      :error="form.errors.password"
      placeholder="••••••••"
      autocomplete="current-password"
      :tabindex="2"
    >
      <template #label-aside>
        <Link
          v-if="canResetPassword"
          :href="route('password.request')"
          :tabindex="5"
          class="text-xs font-bold text-red-600 transition-colors hover:text-neutral-950 hover:underline underline-offset-4"
        >
          Forgot password?
        </Link>
      </template>
    </AuthField>

    <div class="pt-0.5">
      <Checkbox v-model="form.remember" label="Keep me signed in for 30 days" :tabindex="3" />
    </div>

    <AuthSubmit :processing="form.processing" label="Sign In" processing-label="Signing in…" :tabindex="4" />

    <div class="border-t border-neutral-200/80 pt-5 text-center">
      <p class="text-sm text-neutral-600">
        Don't have an account yet?
        <Link
          :href="route('register')"
          :tabindex="6"
          class="ml-1 font-bold text-red-600 transition-colors hover:text-neutral-950 hover:underline underline-offset-4"
        >
          Request access
        </Link>
      </p>
    </div>
  </form>
</template>