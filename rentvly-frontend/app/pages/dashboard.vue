<script setup lang="ts">
// Mock data — replace with real API calls
const user = ref({
  firstName: 'Jean',
  lastName: 'Dupont',
  email: 'jean@exemple.com',
  plan: 'Gratuit',
  memberSince: 'Janvier 2026',
})

const stats = ref([
  { label: 'Estimations', value: '12', icon: 'i-lucide-calculator', trend: '+3 ce mois' },
  { label: 'Biens sauvegardés', value: '5', icon: 'i-lucide-bookmark', trend: '+1 cette semaine' },
  { label: 'Rentabilité moy.', value: '6.2%', icon: 'i-lucide-trending-up', trend: 'Bon rendement' },
  { label: 'Villes analysées', value: '8', icon: 'i-lucide-map-pin', trend: '' },
])

const recentEstimations = ref([
  {
    id: 1,
    city: 'Paris 11e',
    postalCode: '75011',
    type: 'Appartement',
    surface: 45,
    rooms: 2,
    predictedRent: 1280,
    date: '2026-02-14',
    yield: null,
  },
  {
    id: 2,
    city: 'Lyon 3e',
    postalCode: '69003',
    type: 'Appartement',
    surface: 62,
    rooms: 3,
    predictedRent: 890,
    date: '2026-02-12',
    yield: 5.8,
  },
  {
    id: 3,
    city: 'Bordeaux',
    postalCode: '33000',
    type: 'Maison',
    surface: 95,
    rooms: 4,
    predictedRent: 1450,
    date: '2026-02-10',
    yield: 7.1,
  },
  {
    id: 4,
    city: 'Marseille 8e',
    postalCode: '13008',
    type: 'Appartement',
    surface: 38,
    rooms: 2,
    predictedRent: 720,
    date: '2026-02-08',
    yield: 4.3,
  },
  {
    id: 5,
    city: 'Toulouse',
    postalCode: '31000',
    type: 'Appartement',
    surface: 55,
    rooms: 3,
    predictedRent: 780,
    date: '2026-02-05',
    yield: 6.5,
  },
])

const savedProperties = ref([
  { id: 1, city: 'Lyon 6e', type: 'Appartement', surface: 70, rent: 1100, yield: 5.2 },
  { id: 2, city: 'Bordeaux', type: 'Maison', surface: 110, rent: 1600, yield: 7.1 },
  { id: 3, city: 'Nantes', type: 'Appartement', surface: 48, rent: 690, yield: 6.8 },
])

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(value)
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

function getYieldColor(y: number | null): string {
  if (y === null) return 'text-gray-500'
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
  <div class="min-h-screen bg-gray-950">
    <!-- Header -->
    <div class="border-b border-gray-800/60 bg-gray-950">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-white">
              Bonjour, {{ user.firstName }} 👋
            </h1>
            <p class="mt-1 text-sm text-gray-500">
              Voici un résumé de votre activité sur LocaPrix.
            </p>
          </div>
          <UButton to="/predict" color="primary" icon="i-lucide-plus" class="shadow-lg shadow-primary-500/20">
            Nouvelle estimation
          </UButton>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-8">

      <!-- Stats cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="stat in stats"
          :key="stat.label"
          class="p-5 rounded-xl bg-gray-900 border border-gray-800/60 hover:border-gray-700/60 transition-colors"
        >
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center">
              <UIcon :name="stat.icon" class="size-5 text-primary-400" />
            </div>
          </div>
          <p class="text-2xl font-bold text-white">{{ stat.value }}</p>
          <p class="text-xs text-gray-500 mt-1">{{ stat.label }}</p>
          <p v-if="stat.trend" class="text-xs text-primary-400 mt-1">{{ stat.trend }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Recent estimations -->
        <div class="lg:col-span-2">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-white">Estimations récentes</h2>
            <UButton variant="ghost" color="neutral" size="xs" trailing-icon="i-lucide-arrow-right">
              Tout voir
            </UButton>
          </div>

          <div class="bg-gray-900 rounded-xl border border-gray-800/60 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-gray-800/60">
                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Bien</th>
                    <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3 hidden sm:table-cell">Surface</th>
                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3">Loyer estimé</th>
                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Rendement</th>
                    <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">Date</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/40">
                  <tr
                    v-for="est in recentEstimations"
                    :key="est.id"
                    class="hover:bg-gray-800/30 transition-colors cursor-pointer"
                  >
                    <td class="px-5 py-4">
                      <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gray-800 flex items-center justify-center flex-shrink-0">
                          <UIcon
                            :name="est.type === 'Maison' ? 'i-lucide-home' : 'i-lucide-building'"
                            class="size-4 text-gray-400"
                          />
                        </div>
                        <div>
                          <p class="text-sm font-medium text-white">{{ est.city }}</p>
                          <p class="text-xs text-gray-500">{{ est.type }} · {{ est.rooms }}p</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-5 py-4 hidden sm:table-cell">
                      <span class="text-sm text-gray-300">{{ est.surface }} m²</span>
                    </td>
                    <td class="px-5 py-4 text-right">
                      <span class="text-sm font-semibold text-white">{{ formatCurrency(est.predictedRent) }}</span>
                      <span class="text-xs text-gray-500">/mois</span>
                    </td>
                    <td class="px-5 py-4 text-right hidden md:table-cell">
                      <span
                        v-if="est.yield"
                        class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="[getYieldColor(est.yield), getYieldBg(est.yield)]"
                      >
                        {{ est.yield }}%
                      </span>
                      <span v-else class="text-xs text-gray-600">—</span>
                    </td>
                    <td class="px-5 py-4 text-right hidden lg:table-cell">
                      <span class="text-xs text-gray-500">{{ formatDate(est.date) }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

          <!-- Saved properties -->
          <div>
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-white">Biens sauvegardés</h2>
              <UButton variant="ghost" color="neutral" size="xs" icon="i-lucide-bookmark">
                {{ savedProperties.length }}
              </UButton>
            </div>

            <div class="space-y-3">
              <div
                v-for="prop in savedProperties"
                :key="prop.id"
                class="p-4 rounded-xl bg-gray-900 border border-gray-800/60 hover:border-gray-700/60 transition-colors cursor-pointer"
              >
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <UIcon
                      :name="prop.type === 'Maison' ? 'i-lucide-home' : 'i-lucide-building'"
                      class="size-4 text-gray-400"
                    />
                    <span class="text-sm font-medium text-white">{{ prop.city }}</span>
                  </div>
                  <span
                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                    :class="[getYieldColor(prop.yield), getYieldBg(prop.yield)]"
                  >
                    {{ prop.yield }}%
                  </span>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500">
                  <span>{{ prop.surface }} m² · {{ prop.type }}</span>
                  <span class="font-medium text-gray-300">{{ formatCurrency(prop.rent) }}/mois</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick actions -->
          <div>
            <h2 class="text-lg font-semibold text-white mb-4">Actions rapides</h2>
            <div class="space-y-2">
              <UButton
                to="/predict"
                block
                variant="soft"
                color="primary"
                icon="i-lucide-calculator"
                class="justify-start"
              >
                Estimer un loyer
              </UButton>
              <UButton
                block
                variant="soft"
                color="neutral"
                icon="i-lucide-download"
                class="justify-start"
              >
                Exporter mes données
              </UButton>
              <UButton
                block
                variant="soft"
                color="neutral"
                icon="i-lucide-settings"
                class="justify-start"
              >
                Paramètres du compte
              </UButton>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>