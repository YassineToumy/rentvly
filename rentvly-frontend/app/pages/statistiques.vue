<script setup lang="ts">
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8080/api/v1'

type CityRow = {
  city: string
  listings: number
  avg_price: number
  avg_price_m2: number
  avg_surface: number
  yield_index: number
}

type Point = { label: string; listings: number; avg_price?: number; avg_price_m2?: number }

type StatsPayload = {
  overview: {
    total: number
    avg_price: number
    median_price: number
    avg_price_m2: number
    avg_surface: number
    avg_rooms: number
    min_price: number
    max_price: number
  }
  cities: {
    most_expensive: CityRow[]
    highest_price_m2: CityRow[]
    most_rentable: CityRow[]
    most_listings: CityRow[]
  }
  by_type: Array<{ property_type: string; listings: number; avg_price: number; avg_price_m2: number }>
  price_buckets: Array<{ key: string; label: string; listings: number }>
  price_curve: Point[]
  surface_buckets: Point[]
  rooms: Point[]
  timeline: Point[]
  departments: Array<{
    department_code: string
    department_name: string | null
    listings: number
    avg_price: number
    avg_price_m2: number
  }>
  regions: Array<{
    code_region: string
    region_name: string | null
    listings: number
    avg_price: number
    avg_price_m2: number
  }>
  filter_options: {
    regions: Array<{ value: string; label: string }>
    departments: Array<{ value: string; label: string; region: string }>
    cities: Array<{ value: string; label: string }>
  }
}

const selectedType = ref('')
const selectedRegion = ref('')
const selectedDepartment = ref('')
const selectedCity = ref('')
const selectedPrice = ref('')
const selectedSurface = ref('')

const loading = ref(true)
const error = ref<string | null>(null)
const stats = ref<StatsPayload | null>(null)

const typeItems = [
  { label: 'Appartements', value: 'flat' },
  { label: 'Maisons', value: 'house' },
]
const priceItems = [
  { label: '< 150 000 €', value: 'under150' },
  { label: '150–250 k€', value: '150_250' },
  { label: '250–400 k€', value: '250_400' },
  { label: '> 400 000 €', value: 'over400' },
]
const surfaceItems = [
  { label: '< 50 m²', value: 'under50' },
  { label: '50–90 m²', value: '50_90' },
  { label: '> 90 m²', value: 'over90' },
]

const regionItems = computed(() => stats.value?.filter_options.regions || [])
const departmentItems = computed(() => stats.value?.filter_options.departments || [])
const cityItems = computed(() => stats.value?.filter_options.cities || [])

function priceParams() {
  if (selectedPrice.value === 'under150') return { max_price: '150000' }
  if (selectedPrice.value === '150_250') return { min_price: '150000', max_price: '250000' }
  if (selectedPrice.value === '250_400') return { min_price: '250000', max_price: '400000' }
  if (selectedPrice.value === 'over400') return { min_price: '400000' }
  return {}
}

function surfaceParams() {
  if (selectedSurface.value === 'under50') return { max_surface: '50' }
  if (selectedSurface.value === '50_90') return { min_surface: '50', max_surface: '90' }
  if (selectedSurface.value === 'over90') return { min_surface: '90' }
  return {}
}

async function fetchStats() {
  loading.value = true
  error.value = null
  try {
    const query: Record<string, string> = {
      ...priceParams(),
      ...surfaceParams(),
    }
    if (selectedType.value) query.type = selectedType.value
    if (selectedRegion.value) query.region = selectedRegion.value
    if (selectedDepartment.value) query.department = selectedDepartment.value
    if (selectedCity.value) query.city = selectedCity.value

    const res = await $fetch<{ success: boolean; data: StatsPayload }>(`${apiBase}/ventes/stats`, { query })
    if (res.success) stats.value = res.data
  } catch (e: any) {
    error.value = e?.data?.message || 'Impossible de charger les statistiques.'
  } finally {
    loading.value = false
  }
}

let fetchTimer: ReturnType<typeof setTimeout> | null = null
function scheduleFetch() {
  if (fetchTimer) clearTimeout(fetchTimer)
  fetchTimer = setTimeout(fetchStats, 120)
}

watch(selectedRegion, () => {
  selectedDepartment.value = ''
  selectedCity.value = ''
})
watch(selectedDepartment, () => {
  selectedCity.value = ''
})

watch(
  [selectedType, selectedRegion, selectedDepartment, selectedCity, selectedPrice, selectedSurface],
  scheduleFetch,
)

onMounted(fetchStats)

function resetFilters() {
  selectedType.value = ''
  selectedRegion.value = ''
  selectedDepartment.value = ''
  selectedCity.value = ''
  selectedPrice.value = ''
  selectedSurface.value = ''
}

function formatPrice(v: number | null | undefined) {
  if (v == null) return '—'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
  }).format(v)
}

function formatNumber(v: number | null | undefined) {
  if (v == null) return '—'
  return new Intl.NumberFormat('fr-FR').format(Math.round(v))
}

function formatCompact(v: number) {
  if (v >= 1_000_000) return `${(v / 1_000_000).toFixed(1)} M`
  if (v >= 1000) return `${Math.round(v / 1000)} k`
  return String(Math.round(v))
}

function typeLabel(t: string | null) {
  if (t === 'flat') return 'Appartements'
  if (t === 'house') return 'Maisons'
  return t || 'Autre'
}

function barWidth(value: number, max: number) {
  if (!max) return '0%'
  return `${Math.max(6, Math.round((value / max) * 100))}%`
}

function formatMonth(label: string) {
  const [y, m] = label.split('-')
  if (!y || !m) return label
  return `${m}/${y.slice(2)}`
}

const kpis = computed(() => {
  if (!stats.value) return []
  const o = stats.value.overview
  return [
    { label: 'Annonces filtrées', value: formatNumber(o.total), hint: 'Selon les filtres actifs', icon: 'i-lucide-building-2' },
    { label: 'Prix médian', value: formatPrice(o.median_price), hint: `Moyenne ${formatPrice(o.avg_price)}`, icon: 'i-lucide-badge-euro' },
    { label: 'Prix au m²', value: `${formatNumber(o.avg_price_m2)} €`, hint: 'Moyenne du périmètre', icon: 'i-lucide-ruler' },
    { label: 'Surface moyenne', value: `${o.avg_surface} m²`, hint: `${o.avg_rooms} pièces en moyenne`, icon: 'i-lucide-maximize' },
  ]
})

const timelineLabels = computed(() => (stats.value?.timeline || []).map(p => formatMonth(p.label)))
const timelinePrices = computed(() => (stats.value?.timeline || []).map(p => p.avg_price || 0))
const timelineListings = computed(() => (stats.value?.timeline || []).map(p => p.listings))
const priceCurveLabels = computed(() => (stats.value?.price_curve || []).map(p => p.label))
const priceCurveValues = computed(() => (stats.value?.price_curve || []).map(p => p.listings))
const surfaceLabels = computed(() => (stats.value?.surface_buckets || []).map(p => p.label))
const surfaceValues = computed(() => (stats.value?.surface_buckets || []).map(p => p.listings))
const roomsLabels = computed(() => (stats.value?.rooms || []).map(p => `${p.label} p.`))
const roomsPrices = computed(() => (stats.value?.rooms || []).map(p => p.avg_price || 0))
const deptLabels = computed(() => (stats.value?.departments || []).slice(0, 12).map(d => d.department_code))
const deptPrices = computed(() => (stats.value?.departments || []).slice(0, 12).map(d => d.avg_price))

const maxBucket = computed(() => Math.max(...(stats.value?.price_buckets.map(b => b.listings) || [1]), 1))
const maxType = computed(() => Math.max(...(stats.value?.by_type.map(t => t.listings) || [1]), 1))
</script>

<template>
  <div class="min-h-screen">
    <div class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-5">
        <div>
          <h1 class="text-3xl font-bold text-black dark:text-white">Statistiques</h1>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-500">
            Filtrez par lieu, prix et type pour comparer les marchés et voir les courbes.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
          <StatsFilterSelect
            v-model="selectedType"
            label="Type"
            placeholder="Tous les types"
            icon="i-lucide-home"
            :items="typeItems"
          />
          <StatsFilterSelect
            v-model="selectedRegion"
            label="Région"
            placeholder="Toute la France"
            icon="i-lucide-map"
            :items="regionItems"
          />
          <StatsFilterSelect
            v-model="selectedDepartment"
            label="Département"
            placeholder="Tous les départements"
            icon="i-lucide-map-pinned"
            :items="departmentItems"
          />
          <StatsFilterSelect
            v-model="selectedCity"
            label="Ville"
            placeholder="Toutes les villes"
            icon="i-lucide-building-2"
            :items="cityItems"
          />
          <StatsFilterSelect
            v-model="selectedPrice"
            label="Prix"
            placeholder="Tous les prix"
            icon="i-lucide-badge-euro"
            :items="priceItems"
          />
          <StatsFilterSelect
            v-model="selectedSurface"
            label="Surface"
            placeholder="Toutes surfaces"
            icon="i-lucide-ruler"
            :items="surfaceItems"
          />
        </div>
        <div class="flex justify-end">
          <UButton variant="ghost" color="neutral" size="sm" icon="i-lucide-rotate-ccw" @click="resetFilters">
            Réinitialiser les filtres
          </UButton>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-8">
      <div v-if="loading && !stats" class="text-center py-20">
        <UIcon name="i-lucide-loader-2" class="size-8 text-gray-400 animate-spin mx-auto mb-3" />
        <p class="text-sm text-gray-500">Calcul des statistiques…</p>
      </div>

      <UAlert v-else-if="error" color="error" variant="soft" :title="error" />

      <template v-else-if="stats">
        <div v-if="loading" class="text-xs text-gray-500 flex items-center gap-2">
          <UIcon name="i-lucide-loader-2" class="size-3.5 animate-spin" />
          Mise à jour…
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <div
            v-for="kpi in kpis"
            :key="kpi.label"
            class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5"
          >
            <div class="w-9 h-9 rounded-lg bg-primary-500/10 flex items-center justify-center mb-4">
              <UIcon :name="kpi.icon" class="size-4 text-primary-500" />
            </div>
            <p class="text-2xl font-semibold tracking-tight text-black dark:text-white">{{ kpi.value }}</p>
            <p class="text-sm font-medium mt-1">{{ kpi.label }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ kpi.hint }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-sm font-semibold">Évolution du prix moyen</h2>
            <p class="text-xs text-gray-500 mb-3">Courbe mensuelle (24 derniers mois)</p>
            <StatsLineChart :labels="timelineLabels" :values="timelinePrices" :format-tick="formatCompact" />
          </section>
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-sm font-semibold">Volume d'annonces</h2>
            <p class="text-xs text-gray-500 mb-3">Nombre de biens publiés par mois</p>
            <StatsLineChart :labels="timelineLabels" :values="timelineListings" color="#0ea5e9" :format-tick="formatNumber" />
          </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-sm font-semibold">Répartition selon le prix</h2>
            <p class="text-xs text-gray-500 mb-3">Courbe du nombre d'annonces par tranche de 25 k€</p>
            <StatsLineChart :labels="priceCurveLabels" :values="priceCurveValues" color="#f59e0b" :format-tick="formatNumber" />
          </section>
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-sm font-semibold">Prix moyen selon le département</h2>
            <p class="text-xs text-gray-500 mb-3">Les 12 départements les plus chers du filtre</p>
            <StatsLineChart :labels="deptLabels" :values="deptPrices" color="#8b5cf6" :format-tick="formatCompact" />
          </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-sm font-semibold">Annonces selon la surface</h2>
            <StatsLineChart :labels="surfaceLabels" :values="surfaceValues" color="#14b8a6" :format-tick="formatNumber" />
          </section>
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-sm font-semibold">Prix moyen selon le nombre de pièces</h2>
            <StatsLineChart :labels="roomsLabels" :values="roomsPrices" color="#ef4444" :format-tick="formatCompact" />
          </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
              <h2 class="text-sm font-semibold">Villes les plus rentables</h2>
              <p class="text-xs text-gray-500 mt-1">Prix au m² le plus bas vs moyenne du filtre.</p>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
              <li v-for="(row, i) in stats.cities.most_rentable" :key="row.city" class="px-5 py-3">
                <div class="flex items-center justify-between gap-3 mb-1.5">
                  <NuxtLink :to="`/listings?search=${encodeURIComponent(row.city)}`" class="text-sm font-medium hover:text-primary-500 truncate">
                    {{ i + 1 }}. {{ row.city }}
                  </NuxtLink>
                  <span class="text-sm font-semibold text-primary-600 dark:text-primary-400 shrink-0">indice {{ row.yield_index }}</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                  <div class="h-full bg-primary-500 rounded-full" :style="{ width: barWidth(row.yield_index, stats.cities.most_rentable[0]?.yield_index || 1) }" />
                </div>
                <p class="text-[11px] text-gray-500 mt-1">
                  {{ formatNumber(row.avg_price_m2) }} €/m² · {{ formatPrice(row.avg_price) }} · {{ formatNumber(row.listings) }} biens
                </p>
              </li>
              <li v-if="!stats.cities.most_rentable.length" class="px-5 py-8 text-center text-sm text-gray-500">Pas assez de données</li>
            </ul>
          </section>

          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
              <h2 class="text-sm font-semibold">Villes les plus chères</h2>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
              <li v-for="(row, i) in stats.cities.most_expensive" :key="row.city" class="px-5 py-3">
                <div class="flex items-center justify-between gap-3 mb-1.5">
                  <NuxtLink :to="`/listings?search=${encodeURIComponent(row.city)}`" class="text-sm font-medium hover:text-primary-500 truncate">
                    {{ i + 1 }}. {{ row.city }}
                  </NuxtLink>
                  <span class="text-sm font-semibold shrink-0">{{ formatPrice(row.avg_price) }}</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                  <div class="h-full bg-gray-700 dark:bg-gray-300 rounded-full" :style="{ width: barWidth(row.avg_price, stats.cities.most_expensive[0]?.avg_price || 1) }" />
                </div>
                <p class="text-[11px] text-gray-500 mt-1">{{ formatNumber(row.avg_price_m2) }} €/m² · {{ formatNumber(row.listings) }} biens</p>
              </li>
            </ul>
          </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-sm font-semibold mb-4">Tranches de prix</h2>
            <div class="space-y-3">
              <div v-for="bucket in stats.price_buckets" :key="bucket.key">
                <div class="flex justify-between text-xs mb-1">
                  <span class="text-gray-600 dark:text-gray-400">{{ bucket.label }}</span>
                  <span class="font-medium">{{ formatNumber(bucket.listings) }}</span>
                </div>
                <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                  <div class="h-full bg-primary-500 rounded-full" :style="{ width: barWidth(bucket.listings, maxBucket) }" />
                </div>
              </div>
            </div>
          </section>
          <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <h2 class="text-sm font-semibold mb-4">Types de biens</h2>
            <div class="space-y-4">
              <div v-for="row in stats.by_type" :key="row.property_type">
                <div class="flex justify-between text-sm mb-1">
                  <span class="font-medium">{{ typeLabel(row.property_type) }}</span>
                  <span class="text-gray-500">{{ formatNumber(row.listings) }} · {{ formatPrice(row.avg_price) }}</span>
                </div>
                <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                  <div class="h-full bg-gray-800 dark:bg-gray-200 rounded-full" :style="{ width: barWidth(row.listings, maxType) }" />
                </div>
              </div>
            </div>
          </section>
        </div>

        <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <h2 class="text-sm font-semibold">Régions</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b border-gray-200 dark:border-gray-800">
                  <th class="px-5 py-3 font-medium">Région</th>
                  <th class="px-5 py-3 font-medium">Annonces</th>
                  <th class="px-5 py-3 font-medium">Prix moyen</th>
                  <th class="px-5 py-3 font-medium">€/m²</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="r in stats.regions" :key="r.code_region" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                  <td class="px-5 py-3 font-medium">{{ r.region_name || r.code_region }}</td>
                  <td class="px-5 py-3">{{ formatNumber(r.listings) }}</td>
                  <td class="px-5 py-3">{{ formatPrice(r.avg_price) }}</td>
                  <td class="px-5 py-3">{{ formatNumber(r.avg_price_m2) }} €</td>
                </tr>
                <tr v-if="!stats.regions.length">
                  <td colspan="4" class="px-5 py-8 text-center text-gray-500">Pas de code région renseigné sur ces annonces</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <h2 class="text-sm font-semibold">Départements</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b border-gray-200 dark:border-gray-800">
                  <th class="px-5 py-3 font-medium">Département</th>
                  <th class="px-5 py-3 font-medium">Annonces</th>
                  <th class="px-5 py-3 font-medium">Prix moyen</th>
                  <th class="px-5 py-3 font-medium">€/m²</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="d in stats.departments" :key="d.department_code" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                  <td class="px-5 py-3 font-medium">
                    {{ d.department_name || d.department_code }}
                    <span class="text-gray-400 font-normal"> ({{ d.department_code }})</span>
                  </td>
                  <td class="px-5 py-3">{{ formatNumber(d.listings) }}</td>
                  <td class="px-5 py-3">{{ formatPrice(d.avg_price) }}</td>
                  <td class="px-5 py-3">{{ formatNumber(d.avg_price_m2) }} €</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>
