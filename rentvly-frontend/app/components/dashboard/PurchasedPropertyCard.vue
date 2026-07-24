<script setup lang="ts">
import type { PurchasedEstimation } from '../../composables/useEstimations'

const props = defineProps<{
  item: PurchasedEstimation
}>()

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(value)
}

const detailTo = computed(() => `/dashboard/estimations/${props.item.id}`)
</script>

<template>
  <div class="app-card p-5 flex flex-col gap-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h3 class="font-semibold app-heading truncate">
            {{ item.city }}
          </h3>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
            <UIcon name="i-lucide-check-circle-2" class="size-3" />
            Acheté
          </span>
        </div>
        <p class="text-xs app-muted mt-0.5">
          {{ item.property_type_label }} · {{ item.rooms }}p
          <span v-if="item.surface_area"> · {{ item.surface_area }} m²</span>
        </p>
      </div>
      <UButton variant="soft" color="primary" size="xs" icon="i-lucide-arrow-right" @click="navigateTo(detailTo)">
        Détail
      </UButton>
    </div>

    <div v-if="item.investment" class="grid grid-cols-2 gap-3 text-sm">
      <div>
        <p class="text-xs app-muted">Prix d'achat</p>
        <p class="font-semibold app-heading">{{ formatCurrency(item.investment.purchase_price) }}</p>
      </div>
      <div>
        <p class="text-xs app-muted">Rendement net annuel</p>
        <p class="font-semibold text-primary-500">{{ item.investment.net_yield_pct }}%</p>
      </div>
      <div>
        <p class="text-xs app-muted">Cashflow mensuel</p>
        <p
          class="font-semibold"
          :class="item.investment.monthly_cashflow >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500'"
        >
          {{ formatCurrency(item.investment.monthly_cashflow) }}
        </p>
      </div>
      <div>
        <p class="text-xs app-muted">Loyer estimé</p>
        <p class="font-semibold app-heading">{{ formatCurrency(item.predicted_rent) }}/mois</p>
      </div>
    </div>

    <div v-if="item.investment?.horizons?.length">
      <p class="text-xs font-medium app-muted mb-2 uppercase tracking-wide">
        Retour sur investissement
      </p>
      <DashboardInvestmentHorizonGrid :horizons="item.investment.horizons" compact />
    </div>

    <p v-else class="text-xs app-muted">
      Ajoutez un prix d'achat pour calculer les projections.
    </p>
  </div>
</template>
