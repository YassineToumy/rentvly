<script setup lang="ts">
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8080/api/v1'

const properties = ref<any[]>([])
const loading = ref(false)
const meta = ref({ current_page: 1, last_page: 1, per_page: 12, total: 0 })

const searchCity = ref('')
const selectedType = ref('all')
const priceRange = ref('all')
const sortBy = ref('recent')

async function fetchProperties(page = 1) {
  loading.value = true
  try {
    const params: Record<string, string> = {
      page: String(page),
      per_page: '12',
      sort: sortBy.value,
    }
    if (selectedType.value !== 'all') params.type = selectedType.value
    if (searchCity.value.trim()) params.search = searchCity.value.trim()
    if (priceRange.value === 'under200') { params.max_price = '200000' }
    if (priceRange.value === '200to400') { params.min_price = '200000'; params.max_price = '400000' }
    if (priceRange.value === '400to600') { params.min_price = '400000'; params.max_price = '600000' }
    if (priceRange.value === 'over600') { params.min_price = '600000' }

    const query = new URLSearchParams(params).toString()
    const res = await $fetch<any>(`${apiBase}/ventes?${query}`)
    if (res.success) {
      properties.value = res.data
      meta.value = res.meta
    }
  } catch (e) {
    console.error('Failed to fetch properties:', e)
  } finally {
    loading.value = false
  }
}

watch([selectedType, priceRange, sortBy], () => fetchProperties(1))
onMounted(() => fetchProperties())

let searchTimeout: ReturnType<typeof setTimeout>
function onSearchInput(val: string) {
  searchCity.value = val
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchProperties(1), 400)
}

function clearFilters() {
  searchCity.value = ''
  selectedType.value = 'all'
  priceRange.value = 'all'
  sortBy.value = 'recent'
}

function formatPrice(v: number | null): string {
  if (!v) return '—'
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v)
}

const typeLabel = (t: string | null) => {
  if (t === 'flat') return 'Appartement'
  if (t === 'house') return 'Maison'
  if (t === 'programme') return 'Programme neuf'
  return t || '—'
}

const fallbackImage = 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&h=400&fit=crop'
</script>

<template>
  <div class="min-h-screen">
    <!-- HEADER -->
    <div class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-black dark:text-white">Biens en vente</h1>
            <p class="mt-1 text-sm text-gray-500">
              <template v-if="loading">Chargement...</template>
              <template v-else>{{ meta.total }} bien{{ meta.total > 1 ? 's' : '' }} · Page {{ meta.current_page }}/{{ meta.last_page }}</template>
            </p>
          </div>
          <UButton to="/predict" variant="outline" color="neutral" icon="i-lucide-calculator" size="sm">
            Estimer un loyer
          </UButton>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

      <!-- FILTERS -->
      <div class="flex flex-col lg:flex-row gap-4 mb-8 p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="flex-1">
          <UInput :model-value="searchCity" @update:model-value="onSearchInput" placeholder="Rechercher une ville ou code postal..." icon="i-lucide-search" size="md" />
        </div>

        <div class="flex rounded-lg border border-gray-200 dark:border-gray-800 p-0.5 bg-gray-50 dark:bg-gray-950">
          <button
            v-for="opt in [{ value: 'all', label: 'Tous' }, { value: 'flat', label: 'Appartements' }, { value: 'house', label: 'Maisons' }]"
            :key="opt.value"
            class="px-4 py-2 rounded-md text-sm font-medium transition-colors whitespace-nowrap"
            :class="selectedType === opt.value ? 'bg-white dark:bg-gray-800 text-black dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="selectedType = opt.value"
          >{{ opt.label }}</button>
        </div>

        <USelect v-model="priceRange" :items="[
          { label: 'Tout prix', value: 'all' },
          { label: '< 200 000 €', value: 'under200' },
          { label: '200k - 400k €', value: '200to400' },
          { label: '400k - 600k €', value: '400to600' },
          { label: '> 600 000 €', value: 'over600' },
        ]" value-key="value" size="md" />

        <USelect v-model="sortBy" :items="[
          { label: 'Plus récents', value: 'recent' },
          { label: 'Prix croissant', value: 'price-asc' },
          { label: 'Prix décroissant', value: 'price-desc' },
          { label: 'Surface', value: 'surface' },
        ]" value-key="value" size="md" />

        <UButton variant="ghost" color="neutral" icon="i-lucide-x" size="md" @click="clearFilters" class="flex-shrink-0" />
      </div>

      <!-- LOADING -->
      <div v-if="loading && properties.length === 0" class="text-center py-20">
        <UIcon name="i-lucide-loader-2" class="size-8 text-gray-400 animate-spin mx-auto mb-3" />
        <p class="text-sm text-gray-500">Chargement des biens...</p>
      </div>

      <!-- EMPTY -->
      <div v-else-if="!loading && properties.length === 0" class="text-center py-20">
        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 flex items-center justify-center mx-auto mb-4">
          <UIcon name="i-lucide-search-x" class="size-8 text-gray-400" />
        </div>
        <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Aucun bien trouvé</h3>
        <p class="text-sm text-gray-500 mb-6">Essayez de modifier vos filtres.</p>
        <UButton variant="outline" color="neutral" icon="i-lucide-rotate-ccw" @click="clearFilters">Réinitialiser</UButton>
      </div>

      <!-- LIST -->
      <div v-else class="space-y-4">
        <NuxtLink
          v-for="p in properties"
          :key="p.id"
          :to="`/listings/${p.id}`"
          class="group flex flex-col sm:flex-row rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden hover:border-gray-300 dark:hover:border-gray-700 hover:shadow-lg transition-all duration-300 cursor-pointer no-underline"
        >
          <!-- Image -->
          <div class="relative w-full sm:w-56 h-44 sm:h-auto flex-shrink-0 overflow-hidden bg-gray-100 dark:bg-gray-800">
            <img
              :src="p.image || fallbackImage"
              :alt="p.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              loading="lazy"
              @error="($event.target as HTMLImageElement).src = fallbackImage"
            />
            <div class="absolute top-2 left-2 flex gap-1.5">
              <span v-if="p.is_new_property" class="px-1.5 py-0.5 rounded bg-black dark:bg-white text-white dark:text-black text-[10px] font-semibold">Neuf</span>
              <span v-if="p.is_pro" class="px-1.5 py-0.5 rounded bg-blue-600 text-white text-[10px] font-semibold">Pro</span>
            </div>
            <div v-if="p.images_count > 1" class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded bg-black/60 text-white text-[10px] flex items-center gap-1">
              <UIcon name="i-lucide-image" class="size-3" />
              {{ p.images_count }}
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 p-4 sm:p-5 flex flex-col justify-between min-w-0">
            <div>
              <div class="flex items-start justify-between gap-3 mb-2">
                <div class="min-w-0">
                  <h3 class="text-sm font-semibold text-black dark:text-white leading-snug group-hover:underline underline-offset-2 truncate">{{ p.title }}</h3>
                  <div class="flex items-center gap-1.5 mt-1">
                    <UIcon name="i-lucide-map-pin" class="size-3 text-gray-400 flex-shrink-0" />
                    <span class="text-xs text-gray-500 truncate">{{ p.city }} · {{ p.postal_code }}<template v-if="p.department_name"> · {{ p.department_name }}</template></span>
                  </div>
                </div>
                <div class="text-right flex-shrink-0">
                  <p class="text-lg font-bold text-black dark:text-white">{{ formatPrice(p.price) }}</p>
                  <p v-if="p.price_per_sqm" class="text-[11px] text-gray-400">{{ Math.round(p.price_per_sqm) }} €/m²</p>
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-3">
                <span class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800">{{ typeLabel(p.property_type) }}</span>
                <div v-if="p.surface_area" class="flex items-center gap-1">
                  <UIcon name="i-lucide-ruler" class="size-3.5" />
                  <span>{{ p.surface_area }} m²</span>
                </div>
                <div v-if="p.rooms_quantity" class="flex items-center gap-1">
                  <UIcon name="i-lucide-layout-grid" class="size-3.5" />
                  <span>{{ p.rooms_quantity }} pièce{{ p.rooms_quantity > 1 ? 's' : '' }}</span>
                </div>
                <div v-if="p.has_elevator" class="flex items-center gap-1">
                  <UIcon name="i-lucide-arrow-up-down" class="size-3.5" />
                  <span>Ascenseur</span>
                </div>
                <div v-if="p.has_parking" class="flex items-center gap-1">
                  <UIcon name="i-lucide-car" class="size-3.5" />
                  <span>Parking</span>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
              <span v-if="p.owner_name" class="text-xs text-gray-400 truncate mr-3">{{ p.owner_name }}</span>
              <div v-else />
              <div class="flex gap-2 flex-shrink-0">
                <UButton to="/predict" variant="soft" color="primary" size="xs" icon="i-lucide-calculator">Analyser</UButton>
                <button class="p-1.5 rounded-md text-gray-400 hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                  <UIcon name="i-lucide-heart" class="size-4" />
                </button>
              </div>
            </div>
          </div>
        </NuxtLink>
      </div>

      <!-- PAGINATION -->
      <div v-if="meta.last_page > 1" class="mt-12 flex items-center justify-center gap-1">
        <UButton variant="ghost" color="neutral" icon="i-lucide-chevron-left" size="sm" :disabled="meta.current_page <= 1" @click="fetchProperties(meta.current_page - 1)" />
        <UButton :variant="meta.current_page === 1 ? 'solid' : 'ghost'" :color="meta.current_page === 1 ? 'primary' : 'neutral'" size="sm" @click="fetchProperties(1)">1</UButton>
        <span v-if="meta.current_page > 3" class="px-2 text-sm text-gray-500">…</span>
        <template v-for="page in meta.last_page" :key="page">
          <UButton v-if="page !== 1 && page !== meta.last_page && Math.abs(page - meta.current_page) <= 1" :variant="page === meta.current_page ? 'solid' : 'ghost'" :color="page === meta.current_page ? 'primary' : 'neutral'" size="sm" @click="fetchProperties(page)">{{ page }}</UButton>
        </template>
        <span v-if="meta.current_page < meta.last_page - 2" class="px-2 text-sm text-gray-500">…</span>
        <UButton v-if="meta.last_page > 1" :variant="meta.current_page === meta.last_page ? 'solid' : 'ghost'" :color="meta.current_page === meta.last_page ? 'primary' : 'neutral'" size="sm" @click="fetchProperties(meta.last_page)">{{ meta.last_page }}</UButton>
        <UButton variant="ghost" color="neutral" icon="i-lucide-chevron-right" size="sm" :disabled="meta.current_page >= meta.last_page" @click="fetchProperties(meta.current_page + 1)" />
      </div>
    </div>
  </div>
</template>