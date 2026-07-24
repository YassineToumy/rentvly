<script setup lang="ts">
import type { InvestmentHorizon } from '../../composables/useEstimations'

defineProps<{
  horizons: InvestmentHorizon[]
  compact?: boolean
}>()

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(value)
}
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div
      v-for="h in horizons"
      :key="h.years"
      class="rounded-lg border border-gray-200 dark:border-gray-800/60 bg-gray-50 dark:bg-gray-800/40 p-4"
      :class="compact ? 'p-3' : 'p-4'"
    >
      <p class="text-xs font-medium text-primary-500 uppercase tracking-wide mb-2">
        {{ h.years }} an{{ h.years > 1 ? 's' : '' }}
      </p>
      <p class="text-lg font-bold text-gray-900 dark:text-white">
        {{ h.total_return_pct }}%
      </p>
      <p class="text-[10px] text-gray-600 dark:text-gray-500 mt-0.5">
        rendement cumulé
      </p>
      <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
        {{ formatCurrency(h.cumulative_income) }}
      </p>
      <p class="text-[10px] text-gray-500 dark:text-gray-600">
        revenus nets locatifs
      </p>
    </div>
  </div>
</template>
