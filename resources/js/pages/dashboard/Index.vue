<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps<{ view: 'admin' | 'state' | 'zone' | 'lga' | 'ward'; data: Record<string, any> }>()

const AdminView = defineAsyncComponent(() => import('./levels/AdminView.vue'))
const ScopedView = defineAsyncComponent(() => import('./levels/ScopedView.vue'))
const Current = computed(() => (props.view === 'admin' ? AdminView : ScopedView))
const componentData = computed(() => props.data as any)
</script>

<template>
  <Head title="Dashboard" />
  <AppLayout>
    <component :is="Current" v-bind="componentData" />
  </AppLayout>
</template>