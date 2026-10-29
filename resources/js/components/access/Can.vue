<script setup lang="ts">
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps<{ permission?: string; any?: string[]; role?: string[] }>()
const { can, canAny, isRole } = usePermissions()

const allowed = computed(() => {
  if (props.permission) return can(props.permission)
  if (props.any) return canAny(...props.any)
  if (props.role) return isRole(...props.role)
  return true
})
</script>

<template><slot v-if="allowed" /></template>