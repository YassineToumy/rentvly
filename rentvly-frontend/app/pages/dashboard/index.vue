<script setup lang="ts">
const { user, fetchUser } = useAuth()
const { loading, error, stats, estimations, purchased, fetchDashboard } = useEstimations()

const firstName = computed(() => user.value?.name?.split(' ')[0] ?? 'Investisseur')
const listFilter = ref<'all' | 'purchased'>('all')
const estimationsSection = ref<HTMLElement | null>(null)

const filteredEstimations = computed(() => {
  if (listFilter.value === 'purchased') {
    return estimations.value.filter(e => e.is_purchased)
  }
  return estimations.value
})

const statCards = computed(() => [
  {
    key: 'all',
    label: 'Estimations',
    value: String(stats.value.total),
    icon: 'i-lucide-calculator',
    trend: stats.value.total > 0 ? 'Voir la liste' : 'Aucune pour le moment',
  },
  {
    key: 'purchased',
    label: 'Biens achetés',
    value: String(stats.value.purchased_count),
    icon: 'i-lucide-home',
    trend: stats.value.purchased_count > 0 ? 'Portfolio actif' : 'Marquez un bien acheté',
  },
  {
    key: 'yield',
    label: 'Rentabilité moy.',
    value: stats.value.average_net_yield != null ? `${stats.value.average_net_yield}%` : '—',
    icon: 'i-lucide-trending-up',
    trend: stats.value.average_net_yield != null ? 'Sur les biens avec prix d\'achat' : '',
  },
  {
    key: 'invested',
    label: 'Capital investi',
    value: stats.value.portfolio_invested != null
      ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(stats.value.portfolio_invested)
      : '—',
    icon: 'i-lucide-wallet',
    trend: stats.value.purchased_count > 0 ? `${stats.value.purchased_count} bien(s)` : '',
  },
])

onMounted(async () => {
  await fetchUser()
  await fetchDashboard()
})

function onStatClick(key: string) {
  if (key === 'purchased') {
    listFilter.value = 'purchased'
    nextTick(() => estimationsSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
    return
  }
  if (key === 'all') {
    listFilter.value = 'all'
    nextTick(() => estimationsSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
  }
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(value)
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

function getYieldColor(y: number | null): string {
  if (y === null) return 'text-gray-600 dark:text-gray-500'
  if (y >= 7) return 'text-green-400'
  if (y >= 5) return 'text-emerald-400'
  if (y >= 3) return 'text-yellow-400'
  return 'text-red-400'
}

function getYieldBg(y: number | null): string {
  if (y === null) return 'bg-gray-500/10'
  if (y >= 7) return 'bg-green-500/10'
  if (y >= 5) return 'bg-emerald-500/10'
  if (y >= 3) return 'bg-yellow-500/10'
  return 'bg-red-500/10'
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-gray-950">
    <div class="border-b border-gray-200 dark:border-gray-800/60 bg-white dark:bg-gray-950">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              Bonjour, {{ firstName }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-500">
              Votre tableau de bord investisseur Rentvly.
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <UButton to="/listings" variant="outline" color="neutral" icon="i-lucide-building" size="sm">
              Biens en vente
            </UButton>
            <UButton to="/predict" color="primary" icon="i-lucide-plus" size="sm" class="shadow-lg shadow-primary-500/20">
              Nouvelle estimation
            </UButton>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-10">
      <div v-if="loading" class="flex justify-center py-16">
        <UIcon name="i-lucide-loader-2" class="size-8 text-primary-400 animate-spin" />
      </div>

      <UAlert
        v-else-if="error"
        color="error"
        icon="i-lucide-alert-circle"
        :description="error"
      />

      <template v-else>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <DashboardStatCard
            v-for="stat in statCards"
            :key="stat.key"
            :label="stat.label"
            :value="stat.value"
            :icon="stat.icon"
            :trend="stat.trend"
            :active="(stat.key === 'purchased' && listFilter === 'purchased') || (stat.key === 'all' && listFilter === 'all' && stat.key !== 'yield')"
            @click="onStatClick(stat.key)"
          />
        </div>

        <section v-if="purchased.length > 0" class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              Mes biens achetés
            </h2>
            <span class="text-sm text-gray-600 dark:text-gray-500">{{ purchased.length }} bien(s)</span>
          </div>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <DashboardPurchasedPropertyCard
              v-for="item in purchased"
              :key="item.id"
              :item="item"
            />
          </div>
        </section>

        <section ref="estimationsSection">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
              Mes estimations
            </h2>
            <div class="flex items-center gap-2">
              <UButton
                size="xs"
                :variant="listFilter === 'all' ? 'solid' : 'ghost'"
                color="neutral"
                @click="listFilter = 'all'"
              >
                Toutes
              </UButton>
              <UButton
                size="xs"
                :variant="listFilter === 'purchased' ? 'solid' : 'ghost'"
                color="neutral"
                @click="listFilter = 'purchased'"
              >
                Achetées
              </UButton>
              <span class="text-sm text-gray-600 dark:text-gray-500 ml-2">{{ filteredEstimations.length }} / {{ stats.total }}</span>
            </div>
          </div>

          <div
            v-if="filteredEstimations.length === 0"
            class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-12 text-center"
          >
            <UIcon name="i-lucide-calculator" class="size-12 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-600 dark:text-gray-400 mb-4">
              {{ listFilter === 'purchased' ? 'Aucun bien marqué comme acheté.' : 'Vous n\'avez pas encore enregistré d\'estimation.' }}
            </p>
            <UButton to="/predict" color="primary" icon="i-lucide-plus">
              Faire une estimation
            </UButton>
          </div>

          <div v-else class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800/60 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-gray-200 dark:border-gray-800/60">
                    <th class="text-left text-xs font-medium text-gray-600 dark:text-gray-500 uppercase tracking-wider px-5 py-3">Bien</th>
                    <th class="text-left text-xs font-medium text-gray-600 dark:text-gray-500 uppercase tracking-wider px-5 py-3 hidden sm:table-cell">Surface</th>
                    <th class="text-right text-xs font-medium text-gray-600 dark:text-gray-500 uppercase tracking-wider px-5 py-3">Loyer</th>
                    <th class="text-right text-xs font-medium text-gray-600 dark:text-gray-500 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Rendement</th>
                    <th class="text-right text-xs font-medium text-gray-600 dark:text-gray-500 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">Statut</th>
                    <th class="text-right text-xs font-medium text-gray-600 dark:text-gray-500 uppercase tracking-wider px-5 py-3">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800/40">
                  <tr
                    v-for="est in filteredEstimations"
                    :key="est.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors"
                  >
                    <td class="px-5 py-4">
                      <NuxtLink :to="`/dashboard/estimations/${est.id}`" class="flex items-center gap-3 group">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-500/10">
                          <UIcon
                            :name="est.property_type === 'house' ? 'i-lucide-home' : 'i-lucide-building'"
                            class="size-4 text-gray-600 dark:text-gray-400 group-hover:text-primary-400"
                          />
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-500">{{ est.city }}</p>
                          <p class="text-xs text-gray-600 dark:text-gray-500">
                            {{ est.property_type_label }} · {{ est.rooms }}p
                            <span v-if="est.variants_count" class="text-primary-500">
                              · {{ est.variants_count }} variante{{ est.variants_count > 1 ? 's' : '' }}
                            </span>
                          </p>
                        </div>
                      </NuxtLink>
                    </td>
                    <td class="px-5 py-4 hidden sm:table-cell">
                      <span class="text-sm text-gray-700 dark:text-gray-300">{{ est.surface_area ?? '—' }} m²</span>
                    </td>
                    <td class="px-5 py-4 text-right">
                      <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(est.predicted_rent) }}</span>
                      <span class="text-xs text-gray-600 dark:text-gray-500">/mois</span>
                    </td>
                    <td class="px-5 py-4 text-right hidden md:table-cell">
                      <span
                        v-if="est.net_yield != null"
                        class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="[getYieldColor(est.net_yield), getYieldBg(est.net_yield)]"
                      >
                        {{ est.net_yield }}%
                      </span>
                      <span v-else class="text-xs text-gray-600">—</span>
                    </td>
                    <td class="px-5 py-4 text-right hidden lg:table-cell">
                      <span
                        v-if="est.is_purchased"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                      >
                        <UIcon name="i-lucide-check" class="size-3" />
                        Acheté
                      </span>
                      <span v-else class="text-xs text-gray-500">Analysé</span>
                    </td>
                    <td class="px-5 py-4 text-right">
                      <UButton
                        variant="soft"
                        color="primary"
                        size="xs"
                        icon="i-lucide-eye"
                        @click="navigateTo(`/dashboard/estimations/${est.id}`)"
                      >
                        Voir
                      </UButton>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>
