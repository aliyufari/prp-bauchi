<script setup lang="ts">
import { computed } from 'vue'
import { TrendingUp } from 'lucide-vue-next'

const props = defineProps<{
  summary: {
    totalDonations: number
    donationsGrowth: number
    totalExpenditures: number
    expendituresGrowth: number
    balance: number
    balanceGrowth: number
  }
}>()

const hasFinancialData = computed(() => Boolean(
  props.summary.totalDonations || props.summary.totalExpenditures || props.summary.balance,
))

const fmt = (n: number) =>
  '₦' + (n >= 1_000_000
    ? (n / 1_000_000).toFixed(1) + 'M'
    : n >= 1_000
    ? (n / 1_000).toFixed(0) + 'K'
    : n.toString())
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3">
    <div>
      <h3 class="text-sm font-bold tracking-tight text-gray-900">Fundraising & Financial Summary</h3>
      <p class="text-xs text-gray-500 mt-1">Campaign financial overview</p>
    </div>

    <!-- 3 stat blocks -->
    <div v-if="hasFinancialData" class="grid grid-cols-1 gap-2">
      <!-- Donations -->
      <div class="bg-neutral-50 rounded-lg p-2.5">
        <p class="text-[9px] font-bold text-red-700 uppercase tracking-wider">Total Donations</p>
        <p class="text-base font-black text-red-900 mt-0.5">{{ fmt(summary.totalDonations) }}</p>
        <div class="flex items-center gap-1 mt-0.5">
          <TrendingUp class="w-3 h-3 text-red-600" />
          <span class="text-[9px] font-bold text-red-600">+{{ summary.donationsGrowth }}%</span>
        </div>
      </div>

      <!-- Expenditures -->
      <div class="bg-red-50 rounded-lg p-2.5">
        <p class="text-[9px] font-bold text-red-600 uppercase tracking-wider">Total Expenditures</p>
        <p class="text-base font-black text-red-900 mt-0.5">{{ fmt(summary.totalExpenditures) }}</p>
        <div class="flex items-center gap-1 mt-0.5">
          <TrendingUp class="w-3 h-3 text-red-500" />
          <span class="text-[9px] font-bold text-red-500">+{{ summary.expendituresGrowth }}%</span>
        </div>
      </div>

      <!-- Balance -->
      <div class="bg-blue-50 rounded-lg p-2.5">
        <p class="text-[9px] font-bold text-red-900 uppercase tracking-wider">Balance</p>
        <p class="text-base font-black text-blue-900 mt-0.5">{{ fmt(summary.balance) }}</p>
        <div class="flex items-center gap-1 mt-0.5">
          <TrendingUp class="w-3 h-3 text-blue-600" />
          <span class="text-[9px] font-bold text-blue-600">+{{ summary.balanceGrowth }}%</span>
        </div>
      </div>
    </div>

    <div v-else class="rounded-xl bg-gray-50 px-4 py-7 text-center">
      <p class="text-sm font-semibold text-gray-700">Financial data is not connected yet</p>
      <p class="mt-1 text-xs text-gray-500">Donations, expenditure and balance summaries will appear here.</p>
    </div>
  </div>
</template>