<script setup lang="ts">
import StatCard from '@/components/dashboard/StatCard.vue'
import type { StakeholderStatistics } from '@/types/stakeholder'

interface Props {
  statistics: StakeholderStatistics
}
defineProps<Props>()
</script>

<template>
  <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    <StatCard
      :value="statistics.total"
      label="Total Stakeholders"
      icon="Handshake"
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
      :value="statistics.traditional_rulers"
      label="Traditional Rulers"
      icon="Crown"
      icon-bg="bg-gray-100"
      icon-color="text-gray-700"
    />
    <StatCard
      :value="statistics.organizations"
      label="With Organization"
      icon="Building2"
      icon-bg="bg-amber-100"
      icon-color="text-amber-700"
    />
  </div>
</template>