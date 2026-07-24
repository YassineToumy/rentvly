<script setup lang="ts">
import type { EstimationVariant } from '../../composables/useEstimations'

defineProps<{
  variants: EstimationVariant[]
}>()

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(value)
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getYieldColor(y: number | null): string {
  if (y === null) return 'text-gray-600 dark:text-gray-500'
  if (y >= 7) return 'text-green-600 dark:text-green-400'
  if (y >= 5) return 'text-emerald-600 dark:text-emerald-400'
  if (y >= 3) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-500'
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="(variant, index) in variants"
      :key="variant.id"
      class="rounded-xl border border-gray-200 dark:border-gray-800/60 bg-gray-50 dark:bg-gray-800/30 p-4"
    >
      <div class="flex items-center justify-between gap-2 mb-3">
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-500/15 text-primary-600 dark:text-primary-400 text-xs font-bold">
            {{ variants.length - index }}
          </span>
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            Sous-estimation · prix modifié
          </p>
        </div>
        <span class="text-xs text-gray-600 dark:text-gray-500">{{ formatDate(variant.created_at) }}</span>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
        <div>
          <p class="text-xs text-gray-600 dark:text-gray-500">Prix d'achat</p>
          <p class="font-semibold text-gray-900 dark:text-white">
            {{ variant.purchase_price ? formatCurrency(variant.purchase_price) : '—' }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-600 dark:text-gray-500">Loyer estimé</p>
          <p class="font-semibold text-gray-900 dark:text-white">
            {{ formatCurrency(variant.predicted_rent) }}/mois
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-600 dark:text-gray-500">Rendement net</p>
          <p class="font-semibold" :class="getYieldColor(variant.net_yield)">
            {{ variant.net_yield != null ? `${variant.net_yield}%` : '—' }}
          </p>
        </div>
        <div v-if="variant.rentability">
          <p class="text-xs text-gray-600 dark:text-gray-500">Cashflow mensuel</p>
          <p
            class="font-semibold"
            :class="variant.rentability.monthly_cashflow >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500'"
          >
            {{ formatCurrency(variant.rentability.monthly_cashflow) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
