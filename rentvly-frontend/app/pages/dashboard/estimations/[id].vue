<script setup lang="ts">
import type { EstimationDetail } from '../../../composables/useEstimations'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://backend.test/api/v1'
const { authHeaders } = useAuth()
const { deleteEstimation, markAsPurchased, saving } = useEstimations()

const estimation = ref<EstimationDetail | null>(null)
const pageLoading = ref(true)
const pageError = ref<string | null>(null)
const deleting = ref(false)

const canMarkPurchased = computed(() =>
  estimation.value
  && !estimation.value.is_purchased
  && estimation.value.purchase_price != null
  && estimation.value.purchase_price > 0
)

const missingPurchasePrice = computed(() =>
  estimation.value
  && !estimation.value.is_purchased
  && (!estimation.value.purchase_price || estimation.value.purchase_price <= 0)
)

async function loadEstimation() {
  const id = Number(route.params.id)
  if (!id) {
    await navigateTo('/dashboard')
    return
  }

  pageLoading.value = true
  pageError.value = null
  estimation.value = null

  try {
    const res = await $fetch<{ success: boolean; data: EstimationDetail }>(
      `${apiBase}/estimations/${id}`,
      { headers: authHeaders() },
    )
    if (res.success && res.data) {
      estimation.value = res.data
    } else {
      pageError.value = 'Estimation introuvable.'
    }
  } catch (e: any) {
    pageError.value = e?.data?.error || e?.data?.message || 'Estimation introuvable.'
  } finally {
    pageLoading.value = false
  }
}

watch(() => route.params.id, () => {
  if (route.params.id) loadEstimation()
})

onMounted(loadEstimation)

async function handleDelete() {
  if (!estimation.value) return
  deleting.value = true
  const ok = await deleteEstimation(estimation.value.id)
  deleting.value = false
  if (ok) await navigateTo('/dashboard')
}

async function handleMarkPurchased() {
  if (!estimation.value) return
  const updated = await markAsPurchased(estimation.value.id, true)
  if (updated) estimation.value = updated
}

async function handleUnmarkPurchased() {
  if (!estimation.value) return
  const updated = await markAsPurchased(estimation.value.id, false)
  if (updated) estimation.value = updated
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(value)
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getYieldColor(y: number | null): string {
  if (y === null) return 'text-gray-600 dark:text-gray-500'
  if (y >= 7) return 'text-green-400'
  if (y >= 5) return 'text-emerald-400'
  if (y >= 3) return 'text-yellow-400'
  return 'text-red-400'
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-gray-950">
    <div class="border-b border-gray-200 dark:border-gray-800/60 bg-white dark:bg-gray-950">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">
        <UButton
          variant="ghost"
          color="neutral"
          icon="i-lucide-arrow-left"
          size="sm"
          @click="navigateTo('/dashboard')"
        >
          Retour au tableau de bord
        </UButton>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
      <div v-if="pageLoading" class="flex justify-center py-20">
        <UIcon name="i-lucide-loader-2" class="size-8 text-primary-400 animate-spin" />
      </div>

      <UAlert
        v-else-if="pageError"
        color="error"
        icon="i-lucide-alert-circle"
        :description="pageError"
      />

      <div v-else-if="!estimation && !pageLoading && !pageError" class="text-center py-20">
        <p class="text-gray-600 dark:text-gray-400 mb-4">Estimation introuvable.</p>
        <UButton to="/dashboard" variant="soft" color="primary">
          Retour au tableau de bord
        </UButton>
      </div>

      <template v-else-if="estimation">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-8">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ estimation.city }}
                <span v-if="estimation.postal_code" class="text-gray-600 dark:text-gray-500 text-lg font-normal">
                  ({{ estimation.postal_code }})
                </span>
              </h1>
              <span
                v-if="estimation.is_purchased"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"
              >
                <UIcon name="i-lucide-check-circle-2" class="size-3.5" />
                Bien acheté
              </span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-500 mt-1">
              {{ estimation.property_type_label }} · {{ estimation.rooms }} pièces
              <span v-if="estimation.surface_area"> · {{ estimation.surface_area }} m²</span>
              · {{ formatDate(estimation.created_at) }}
            </p>
            <p v-if="estimation.purchased_at" class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
              Acheté le {{ formatDate(estimation.purchased_at) }}
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <UButton
              v-if="canMarkPurchased"
              color="primary"
              icon="i-lucide-shopping-bag"
              :loading="saving"
              @click="handleMarkPurchased"
            >
              Marquer comme acheté
            </UButton>
            <UButton
              v-if="estimation.is_purchased"
              variant="outline"
              color="neutral"
              icon="i-lucide-undo-2"
              :loading="saving"
              @click="handleUnmarkPurchased"
            >
              Retirer le statut acheté
            </UButton>
            <UButton
              color="error"
              variant="soft"
              icon="i-lucide-trash-2"
              :loading="deleting"
              @click="handleDelete"
            >
              Supprimer
            </UButton>
          </div>
        </div>

        <UAlert
          v-if="missingPurchasePrice"
          color="warning"
          icon="i-lucide-info"
          class="mb-6"
          title="Prix d'achat manquant"
          description="Calculez la rentabilité avec un prix d'achat (depuis l'annonce ou une nouvelle estimation) avant de marquer ce bien comme acheté."
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <UCard class="bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800">
            <template #header>
              <h2 class="font-semibold text-gray-900 dark:text-white">Loyer estimé</h2>
            </template>
            <p class="text-4xl font-bold text-primary-400">
              {{ formatCurrency(estimation.prediction.predicted_rent) }}
              <span class="text-sm font-normal text-gray-600 dark:text-gray-500">/mois</span>
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-500 mt-3">
              Fourchette :
              {{ formatCurrency(estimation.prediction.confidence_range.low) }}
              –
              {{ formatCurrency(estimation.prediction.confidence_range.high) }}
              (±{{ estimation.prediction.confidence_range.mape_pct }}%)
            </p>
          </UCard>

          <UCard v-if="estimation.rentability" class="bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800">
            <template #header>
              <h2 class="font-semibold text-gray-900 dark:text-white">
                Rentabilité
                <span class="text-xs font-normal text-gray-600 dark:text-gray-500">(estimation principale)</span>
              </h2>
            </template>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-500">Prix d'achat</span>
                <span class="text-gray-900 dark:text-white font-medium">{{ formatCurrency(estimation.rentability.purchase_price) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-500">Rendement brut</span>
                <span :class="getYieldColor(estimation.rentability.gross_yield)" class="font-medium">
                  {{ estimation.rentability.gross_yield }}%
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-500">Rendement net</span>
                <span :class="getYieldColor(estimation.rentability.net_yield)" class="font-medium">
                  {{ estimation.rentability.net_yield }}%
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-500">Cashflow mensuel</span>
                <span class="font-medium" :class="estimation.rentability.monthly_cashflow >= 0 ? 'text-green-400' : 'text-red-400'">
                  {{ formatCurrency(estimation.rentability.monthly_cashflow) }}
                </span>
              </div>
            </div>
          </UCard>
        </div>

        <UCard
          v-if="estimation.variants && estimation.variants.length > 0"
          class="mt-6 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800"
        >
          <template #header>
            <div>
              <h2 class="font-semibold text-gray-900 dark:text-white">
                Historique des prix (sous-estimations)
              </h2>
              <p class="text-xs text-gray-600 dark:text-gray-500 mt-1">
                Chaque ligne correspond à un nouveau prix d'achat testé pour ce même bien.
              </p>
            </div>
          </template>
          <DashboardEstimationVariantsList :variants="estimation.variants" />
        </UCard>

        <UCard
          v-if="estimation.is_purchased && estimation.investment"
          class="mt-6 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800"
        >
          <template #header>
            <div>
              <h2 class="font-semibold text-gray-900 dark:text-white">
                Retour sur investissement
              </h2>
              <p class="text-xs text-gray-600 dark:text-gray-500 mt-1">
                Projections basées sur le cashflow net annuel (loyer − charges − 30 % frais estimés).
              </p>
            </div>
          </template>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 text-sm">
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
              <p class="text-xs text-gray-600 dark:text-gray-500">Revenu net annuel</p>
              <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                {{ formatCurrency(estimation.investment.net_annual_income) }}
              </p>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
              <p class="text-xs text-gray-600 dark:text-gray-500">Rendement net / an</p>
              <p class="text-lg font-bold text-primary-500 mt-1">
                {{ estimation.investment.net_yield_pct }}%
              </p>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
              <p class="text-xs text-gray-600 dark:text-gray-500">Cashflow mensuel</p>
              <p
                class="text-lg font-bold mt-1"
                :class="estimation.investment.monthly_cashflow >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500'"
              >
                {{ formatCurrency(estimation.investment.monthly_cashflow) }}
              </p>
            </div>
          </div>

          <DashboardInvestmentHorizonGrid :horizons="estimation.investment.horizons" />
        </UCard>

        <UCard class="mt-6 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800">
          <template #header>
            <h2 class="font-semibold text-gray-900 dark:text-white">Caractéristiques du bien</h2>
          </template>
          <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div>
              <dt class="text-gray-600 dark:text-gray-500">Ville</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ estimation.form.city }}</dd>
            </div>
            <div>
              <dt class="text-gray-600 dark:text-gray-500">Code postal</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ estimation.form.postal_code }}</dd>
            </div>
            <div v-if="estimation.form.district_name">
              <dt class="text-gray-600 dark:text-gray-500">Quartier</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ estimation.form.district_name }}</dd>
            </div>
            <div>
              <dt class="text-gray-600 dark:text-gray-500">Surface</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ estimation.form.surface_area }} m²</dd>
            </div>
            <div>
              <dt class="text-gray-600 dark:text-gray-500">Pièces / chambres</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ estimation.form.rooms }} / {{ estimation.form.bedrooms }}</dd>
            </div>
            <div>
              <dt class="text-gray-600 dark:text-gray-500">Classe énergie</dt>
              <dd class="text-gray-900 dark:text-white font-medium">{{ estimation.form.energy_class }}</dd>
            </div>
          </dl>
        </UCard>
      </template>
    </div>
  </div>
</template>
