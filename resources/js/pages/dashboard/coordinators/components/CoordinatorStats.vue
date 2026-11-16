<script setup lang="ts">
import StatCard from '@/components/dashboard/StatCard.vue'
import type { CoordinatorStatistics } from '@/types/coordinator'

interface Props {
  statistics: CoordinatorStatistics
}
defineProps<Props>()
</script>

<template>
  <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    <StatCard
      :value="statistics.total"
      label="Total Coordinators"
      icon="Users"
      icon-bg="bg-red-100"
      icon-color="text-red-700"
    />
    <StatCard
      :value="statistics.active"
      label="Active"
      icon="UserCheck"
      icon-bg="bg-neutral-50"
      icon-color="text-red-900"
    />
    <StatCard
      :value="statistics.state_level"
      label="State-level"
      icon="Building2"
      icon-bg="bg-gray-100"
      icon-color="text-gray-700"
    />
    <StatCard
      :value="statistics.assigned"
      label="Assigned to LGA"
      icon="MapPin"
      icon-bg="bg-amber-100"
      icon-color="text-amber-700"
    />
  </div>
</template>