<script setup lang="ts">
import StatCard from '@/components/dashboard/StatCard.vue'
import type { ReturningOfficerStatistics } from '@/types/returning-officer'

interface Props {
  statistics: ReturningOfficerStatistics
}
defineProps<Props>()
</script>

<template>
  <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    <StatCard
      :value="statistics.total"
      label="Total Officers"
      icon="RotateCcw"
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
      :value="statistics.assigned"
      label="Assigned"
      icon="MapPin"
      icon-bg="bg-gray-100"
      icon-color="text-gray-700"
    />
    <StatCard
      :value="statistics.institutions"
      label="Institutions"
      icon="Building2"
      icon-bg="bg-amber-100"
      icon-color="text-amber-700"
    />
  </div>
</template>