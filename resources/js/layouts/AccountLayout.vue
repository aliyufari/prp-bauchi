<script setup lang="ts">
import { computed } from 'vue'
import AppLayout from './AppLayout.vue'
import PortalLayout from './PortalLayout.vue'
import { usePermissions } from '@/composables/usePermissions'

const { auth } = usePermissions()
const Shell = computed(() => (auth.value.layout === 'portal' ? PortalLayout : AppLayout))
</script>

<template>
  <component :is="Shell"><slot /></component>
</template>