<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Mail } from 'lucide-vue-next'
import AuthField from '@/components/auth/AuthField.vue'
import AuthNotice from '@/components/auth/AuthNotice.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'
import { useAuthPanel } from '@/composables/useAuthPanel'

defineProps<{ status?: string }>()

useAuthPanel({
  badge: 'Account Recovery',
  lines: [{ text: 'Regain' }, { text: 'access', accent: true }, { text: 'in minutes.' }],
  text: "Enter the email on your account and we'll send you a link to set a new password.",
})

const form = useForm({ email: '' })

const submit = () => form.post(route('password.email'))
</script>

<template>
  <Head title="Forgot Password – PRP Bauchi State" />

  <div class="mb-6 sm:mb-8">
    <h2 class="text-[#111827] font-black text-xl sm:text-2xl tracking-tight">Forgot your password?</h2>
    <p class="text-[#6b7280] text-sm mt-1">We'll email you a link to reset it</p>
  </div>

  <AuthNotice v-if="status">{{ status }}</AuthNotice>

  <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
    <AuthField id="email" v-model="form.email" label="Email address" type="email" :icon="Mail" :error="form.errors.email" placeholder="you@example.com" autocomplete="email" :tabindex="1" autofocus />

    <AuthSubmit :processing="form.processing" label="Email reset link" processing-label="Sending…" :tabindex="2" />

    <Link :href="route('login')" :tabindex="3" class="mt-1 inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-[#16a34a] hover:text-[#0d3b26] transition-colors">
      <ArrowLeft class="h-4 w-4" />
      Back to sign in
    </Link>
  </form>
</template>