<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Lock, Mail } from 'lucide-vue-next'
import AuthField from '@/components/auth/AuthField.vue'
import AuthNotice from '@/components/auth/AuthNotice.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'
import { useAuthPanel } from '@/composables/useAuthPanel'

defineProps<{ status?: string; canResetPassword: boolean }>()

useAuthPanel() // default copy

const form = useForm({ email: '', password: '', remember: false })

const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') })
</script>

<template>
  <Head title="Sign In – PRP Bauchi State" />

  <div class="mb-6 sm:mb-8">
    <h2 class="text-[#111827] font-black text-xl sm:text-2xl tracking-tight">Sign in to your account</h2>
    <p class="text-[#6b7280] text-sm mt-1">Enter your credentials to access the dashboard</p>
  </div>

  <AuthNotice v-if="status">{{ status }}</AuthNotice>

  <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
    <AuthField
      id="email" v-model="form.email" label="Email address" type="email" :icon="Mail"
      :error="form.errors.email" placeholder="you@example.com" autocomplete="email" :tabindex="1" autofocus
    />

    <AuthField
      id="password" v-model="form.password" label="Password" type="password" :icon="Lock"
      :error="form.errors.password" placeholder="••••••••" autocomplete="current-password" :tabindex="2"
    >
      <template #label-aside>
        <Link
          v-if="canResetPassword" :href="route('password.request')" :tabindex="5"
          class="text-xs font-semibold text-[#16a34a] hover:text-[#0d3b26] underline-offset-2 hover:underline transition-colors"
        >Forgot password?</Link>
      </template>
    </AuthField>

    <label class="flex w-fit cursor-pointer select-none items-center gap-2.5">
      <input v-model="form.remember" type="checkbox" :tabindex="3" class="h-4 w-4 rounded border-gray-300 text-[#16a34a] focus:ring-[#16a34a]" />
      <span class="text-sm text-[#374151]">Remember me for 30 days</span>
    </label>

    <AuthSubmit :processing="form.processing" label="Sign in" processing-label="Signing in…" :tabindex="4" />

    <p class="mt-1 text-center text-sm text-[#6b7280]">
      Don't have an account?
      <Link :href="route('register')" :tabindex="6" class="ml-1 font-semibold text-[#16a34a] hover:text-[#0d3b26] underline-offset-2 hover:underline transition-colors">
        Request access
      </Link>
    </p>
  </form>
</template>