<script setup lang="ts">
import { usePrediction } from '../../composables/usePrediction'

const route = useRoute()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || 'http://localhost:8080/api/v1'

const property = ref<any>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const activeImage = ref(0)

const {
  form,
  loading: predLoading,
  error: predError,
  predictionResult,
  rentabilityResult,
  predictRent,
  calculateRentability,
  resetForm,
} = usePrediction()

const purchasePrice = ref<number | null>(null)

async function fetchProperty() {
  loading.value = true
  error.value = null
  try {
    const id = Array.isArray(route.params.id) ? route.params.id[0] : route.params.id
    const res = await $fetch<any>(`${apiBase}/ventes/${id}`)
    if (res.success) {
      property.value = res.data
      // Pre-fill purchase price from property price
      purchasePrice.value = res.data.price || null
    }
  } catch (e: any) {
    error.value = e?.data?.message || 'Impossible de charger ce bien.'
  } finally {
    loading.value = false
  }
}

function mapPropertyToForm(p: any) {
  const allFeatures = {
    ...(p.interior_features || {}),
    ...(p.exterior_features || {}),
    ...(p.other_features || {}),
  }

  form.city = p.city || ''
  form.postal_code = p.postal_code || ''
  form.district_name = p.district_name || ''
  form.property_type = p.property_type === 'house' ? 'house' : 'flat'
  form.surface_area = p.surface_area || null
  form.rooms = p.rooms_quantity || 1
  form.bedrooms = p.bedrooms_quantity || 0
  form.bathrooms = p.bathrooms_quantity || 0
  form.shower_rooms = p.shower_rooms || 0
  form.toilets = p.toilets || 0
  form.floor = p.floor || 0
  form.total_floors = p.total_floors || 0
  form.land_surface = p.land_surface || 0
  form.year_built = p.year_built || 2000
  form.is_new = p.is_new_property || false
  form.is_furnished = p.is_furnished || false
  form.energy_class = p.energy_class || 'D'
  form.energy_value = p.energy_value || 0
  form.greenhouse_value = p.greenhouse_value || 0
  form.heating_type = p.heating_type || 'individual'
  form.charges = p.charges || 0

  // Equipment from JSONB feature columns
  form.has_elevator = allFeatures.has_elevator || false
  form.has_garden = allFeatures.has_garden || false
  form.has_terrace = allFeatures.has_terrace || false
  form.has_balcony = allFeatures.has_balcony || false
  form.has_pool = allFeatures.has_pool || false
  form.has_cellar = allFeatures.has_cellar || false
  form.has_air_conditioning = allFeatures.has_air_conditioning || false
  form.has_fireplace = allFeatures.has_fireplace || false
  form.has_intercom = allFeatures.has_intercom || false
  form.has_separate_toilet = allFeatures.has_separate_toilet || false
  form.parking_places = p.parking_places || (allFeatures.has_parking ? 1 : 0)
  form.garages = p.garages || 0
}

async function handleEstimate() {
  if (!property.value) return
  mapPropertyToForm(property.value)
  await predictRent()
}

async function handleRentability() {
  if (!purchasePrice.value || purchasePrice.value <= 0) return
  await calculateRentability(purchasePrice.value)
}

onMounted(fetchProperty)

function formatPrice(v: number | null): string {
  if (!v) return '—'
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v)
}

function formatCurrency(v: number): string {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(v)
}

function getYieldColor(yieldPct: number): string {
  if (yieldPct >= 7) return 'text-green-500'
  if (yieldPct >= 5) return 'text-emerald-500'
  if (yieldPct >= 3) return 'text-yellow-500'
  return 'text-red-500'
}

const typeLabel = (t: string | null) => {
  if (t === 'flat') return 'Appartement'
  if (t === 'house') return 'Maison'
  if (t === 'programme') return 'Programme neuf'
  return t || '—'
}

const fallbackImage = 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&h=400&fit=crop'

function nextImage() {
  if (property.value?.photos?.length) {
    activeImage.value = (activeImage.value + 1) % property.value.photos.length
  }
}
function prevImage() {
  if (property.value?.photos?.length) {
    activeImage.value = (activeImage.value - 1 + property.value.photos.length) % property.value.photos.length
  }
}

function getFeatures(p: any): { label: string; active: boolean }[] {
  const map: Record<string, string> = {
    is_disabled_friendly: 'Accessible PMR',
    has_elevator: 'Ascenseur',
    has_garden: 'Jardin',
    has_terrace: 'Terrasse',
    has_balcony: 'Balcon',
    has_pool: 'Piscine',
    has_parking: 'Parking',
    has_cellar: 'Cave',
    has_air_conditioning: 'Climatisation',
    has_fireplace: 'Cheminée',
  }
  const all = { ...(p.interior_features || {}), ...(p.exterior_features || {}), ...(p.other_features || {}) }
  return Object.entries(all).map(([k, v]) => ({ label: map[k] || k, active: v as boolean }))
}
</script>

<template>
  <div class="min-h-screen">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-32">
      <UIcon name="i-lucide-loader-2" class="size-8 text-gray-400 animate-spin" />
    </div>

    <!-- Error -->
    <div v-else-if="error" class="max-w-3xl mx-auto px-4 py-20 text-center">
      <UIcon name="i-lucide-alert-circle" class="size-12 text-red-400 mx-auto mb-4" />
      <h2 class="text-xl font-semibold text-white mb-2">Erreur</h2>
      <p class="text-gray-400 mb-6">{{ error }}</p>
      <UButton to="/listings" variant="outline" color="neutral" icon="i-lucide-arrow-left">Retour aux annonces</UButton>
    </div>

    <!-- Detail -->
    <div v-else-if="property">
      <!-- Back bar -->
      <div class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4">
          <UButton to="/listings" variant="ghost" color="neutral" icon="i-lucide-arrow-left" size="sm">Retour aux annonces</UButton>
        </div>
      </div>

      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

          <!-- LEFT -->
          <div class="lg:col-span-2 space-y-6">

            <!-- Gallery -->
            <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-800 bg-gray-100 dark:bg-gray-900">
              <div class="relative h-72 sm:h-96">
                <img
                  :src="property.photos?.length ? property.photos[activeImage] : fallbackImage"
                  :alt="property.title"
                  class="w-full h-full object-cover"
                  @error="($event.target as HTMLImageElement).src = fallbackImage"
                />
                <template v-if="property.photos?.length > 1">
                  <button class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/50 backdrop-blur text-white flex items-center justify-center hover:bg-black/70" @click="prevImage">
                    <UIcon name="i-lucide-chevron-left" class="size-5" />
                  </button>
                  <button class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/50 backdrop-blur text-white flex items-center justify-center hover:bg-black/70" @click="nextImage">
                    <UIcon name="i-lucide-chevron-right" class="size-5" />
                  </button>
                  <div class="absolute bottom-3 right-3 px-2 py-1 rounded-md bg-black/60 text-white text-xs">
                    {{ activeImage + 1 }} / {{ property.photos.length }}
                  </div>
                </template>
              </div>
              <div v-if="property.photos?.length > 1" class="flex gap-1 p-2 overflow-x-auto">
                <button
                  v-for="(img, i) in property.photos"
                  :key="i"
                  class="w-16 h-12 rounded-md overflow-hidden flex-shrink-0 border-2 transition-colors"
                  :class="i === activeImage ? 'border-primary-500' : 'border-transparent opacity-60 hover:opacity-100'"
                  @click="activeImage = Number(i)"
                >
                  <img :src="img" class="w-full h-full object-cover" @error="($event.target as HTMLImageElement).src = fallbackImage" />
                </button>
              </div>
            </div>

            <!-- Title -->
            <div>
              <h1 class="text-2xl font-bold text-black dark:text-white">{{ property.title }}</h1>
              <div class="flex items-center gap-2 mt-2">
                <UIcon name="i-lucide-map-pin" class="size-4 text-gray-400" />
                <span class="text-sm text-gray-500">
                  {{ property.city }}
                  <template v-if="property.postal_code"> · {{ property.postal_code }}</template>
                  <template v-if="property.department_name"> · {{ property.department_name }}</template>
                  <template v-if="property.region_name"> · {{ property.region_name }}</template>
                </span>
              </div>
            </div>

            <!-- Key specs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-center">
                <UIcon name="i-lucide-home" class="size-5 text-gray-400 mx-auto mb-1" />
                <p class="text-xs text-gray-500">Type</p>
                <p class="text-sm font-semibold text-black dark:text-white">{{ typeLabel(property.property_type) }}</p>
              </div>
              <div v-if="property.surface_area" class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-center">
                <UIcon name="i-lucide-ruler" class="size-5 text-gray-400 mx-auto mb-1" />
                <p class="text-xs text-gray-500">Surface</p>
                <p class="text-sm font-semibold text-black dark:text-white">{{ property.surface_area }} m²</p>
              </div>
              <div v-if="property.rooms_quantity" class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-center">
                <UIcon name="i-lucide-layout-grid" class="size-5 text-gray-400 mx-auto mb-1" />
                <p class="text-xs text-gray-500">Pièces</p>
                <p class="text-sm font-semibold text-black dark:text-white">{{ property.rooms_quantity }}</p>
              </div>
              <div v-if="property.equipment_score" class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-center">
                <UIcon name="i-lucide-star" class="size-5 text-gray-400 mx-auto mb-1" />
                <p class="text-xs text-gray-500">Équipement</p>
                <p class="text-sm font-semibold text-black dark:text-white">{{ property.equipment_score }}/10</p>
              </div>
            </div>

            <!-- Description -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
              <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Description</h2>
              <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-line">
                {{ property.description || 'Aucune description disponible.' }}
              </p>
            </div>

            <!-- Details -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
              <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Détails</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-8 text-sm">
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Type de bien</span>
                  <span class="font-medium text-black dark:text-white">{{ typeLabel(property.property_type) }}</span>
                </div>
                <div v-if="property.surface_area" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Surface</span>
                  <span class="font-medium text-black dark:text-white">{{ property.surface_area }} m²</span>
                </div>
                <div v-if="property.rooms_quantity" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Pièces</span>
                  <span class="font-medium text-black dark:text-white">{{ property.rooms_quantity }}</span>
                </div>
                <div v-if="property.surface_per_room" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Surface / pièce</span>
                  <span class="font-medium text-black dark:text-white">{{ property.surface_per_room }} m²</span>
                </div>
                <div v-if="property.price_per_sqm" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Prix au m²</span>
                  <span class="font-medium text-black dark:text-white">{{ formatPrice(property.price_per_sqm) }}</span>
                </div>
                <div v-if="property.is_new_property" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">État</span>
                  <span class="font-medium text-black dark:text-white">Neuf</span>
                </div>
                <div v-if="property.delivery_date" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Livraison prévue</span>
                  <span class="font-medium text-black dark:text-white">{{ new Date(property.delivery_date).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) }}</span>
                </div>
                <div v-if="property.price_has_decreased" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Prix</span>
                  <span class="font-medium text-green-500">Prix en baisse</span>
                </div>
              </div>
            </div>

            <!-- Features -->
            <div v-if="getFeatures(property).some(f => f.active)" class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
              <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Équipements</h2>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="feat in getFeatures(property).filter(f => f.active)"
                  :key="feat.label"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 text-sm"
                >
                  <UIcon name="i-lucide-check" class="size-3.5" />
                  {{ feat.label }}
                </span>
              </div>
            </div>

            <!-- Location -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6">
              <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Localisation</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-8 text-sm">
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Ville</span>
                  <span class="font-medium text-black dark:text-white">{{ property.city }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Code postal</span>
                  <span class="font-medium text-black dark:text-white">{{ property.postal_code }}</span>
                </div>
                <div v-if="property.department_name" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Département</span>
                  <span class="font-medium text-black dark:text-white">{{ property.department_name }} ({{ property.department_code }})</span>
                </div>
                <div v-if="property.region_name" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Région</span>
                  <span class="font-medium text-black dark:text-white">{{ property.region_name }}</span>
                </div>
                <div v-if="property.commune_name" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Commune</span>
                  <span class="font-medium text-black dark:text-white">{{ property.commune_name }}</span>
                </div>
                <div v-if="property.district_name" class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                  <span class="text-gray-500">Quartier</span>
                  <span class="font-medium text-black dark:text-white">{{ property.district_name }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT: Sidebar -->
          <div class="space-y-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 sticky top-24 space-y-6">

              <!-- Price -->
              <div class="text-center">
                <p class="text-3xl font-bold text-black dark:text-white">{{ formatPrice(property.price) }}</p>
                <p v-if="property.price_per_sqm" class="text-sm text-gray-400 mt-1">{{ Math.round(property.price_per_sqm) }} €/m²</p>
              </div>

              <!-- Owner -->
              <div v-if="property.owner_name" class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                  <UIcon :name="property.is_pro ? 'i-lucide-building-2' : 'i-lucide-user'" class="size-5 text-gray-500" />
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-black dark:text-white truncate">{{ property.owner_name }}</p>
                  <p class="text-xs text-gray-400">{{ property.is_pro ? 'Professionnel' : 'Particulier' }}</p>
                </div>
              </div>

              <USeparator />

              <!-- Estimation section -->
              <div>
                <div class="flex items-center gap-2 mb-3">
                  <UIcon name="i-lucide-calculator" class="size-4 text-primary-500" />
                  <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Estimation du loyer</h3>
                </div>

                <!-- Initial state: button -->
                <div v-if="!predictionResult && !predLoading">
                  <UAlert
                    v-if="predError"
                    color="error"
                    icon="i-lucide-alert-circle"
                    :description="predError"
                    class="mb-3"
                  />
                  <UButton
                    block
                    color="primary"
                    size="lg"
                    icon="i-lucide-calculator"
                    class="shadow-lg shadow-primary-500/20"
                    @click="handleEstimate"
                  >
                    Estimer le loyer
                  </UButton>
                </div>

                <!-- Loading -->
                <div v-else-if="predLoading" class="text-center py-4">
                  <UIcon name="i-lucide-loader-2" class="size-6 animate-spin text-primary-500 mx-auto" />
                  <p class="text-sm text-gray-400 mt-2">Estimation en cours...</p>
                </div>

                <!-- Result -->
                <div v-else-if="predictionResult" class="space-y-3">
                  <div class="rounded-lg bg-primary-50 dark:bg-primary-950 border border-primary-200 dark:border-primary-800 p-4 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Loyer mensuel estimé</p>
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                      {{ formatCurrency(predictionResult.predicted_rent) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                      {{ formatCurrency(predictionResult.confidence_range.low) }} – {{ formatCurrency(predictionResult.confidence_range.high) }}
                    </p>
                    <p class="text-xs text-gray-400">Marge d'erreur : ±{{ predictionResult.confidence_range.mape_pct }}%</p>
                  </div>

                  <div class="grid grid-cols-2 gap-2 text-xs text-center">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2">
                      <p class="text-gray-400">Loyer annuel</p>
                      <p class="font-semibold text-black dark:text-white">{{ formatCurrency(predictionResult.predicted_rent * 12) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-2">
                      <p class="text-gray-400">Prix au m²/mois</p>
                      <p class="font-semibold text-black dark:text-white">
                        {{ property.surface_area ? formatCurrency(predictionResult.predicted_rent / property.surface_area) : '—' }}/m²
                      </p>
                    </div>
                  </div>

                  <UButton
                    block
                    variant="ghost"
                    color="neutral"
                    size="sm"
                    icon="i-lucide-rotate-ccw"
                    @click="resetForm()"
                  >
                    Recalculer
                  </UButton>
                </div>
              </div>

              <!-- Rentability calculator (shown after prediction) -->
              <div v-if="predictionResult">
                <USeparator class="mb-4" />
                <div class="flex items-center gap-2 mb-3">
                  <UIcon name="i-lucide-trending-up" class="size-4 text-primary-500" />
                  <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Rentabilité locative</h3>
                </div>

                <div class="space-y-3">
                  <UFormField label="Prix d'achat (€)">
                    <UInput
                      v-model.number="purchasePrice"
                      type="number"
                      min="1000"
                      placeholder="250000"
                      icon="i-lucide-euro"
                      size="sm"
                    />
                  </UFormField>

                  <UButton
                    block
                    color="primary"
                    variant="soft"
                    size="sm"
                    icon="i-lucide-bar-chart"
                    :loading="predLoading"
                    :disabled="!purchasePrice || purchasePrice <= 0"
                    @click="handleRentability"
                  >
                    Calculer la rentabilité
                  </UButton>

                  <!-- Rentability results -->
                  <div v-if="rentabilityResult" class="space-y-3 pt-1">
                    <div class="grid grid-cols-2 gap-2">
                      <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs text-gray-500 mb-1">Rendement brut</p>
                        <p class="text-lg font-bold" :class="getYieldColor(rentabilityResult.gross_yield)">
                          {{ rentabilityResult.gross_yield }}%
                        </p>
                      </div>
                      <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs text-gray-500 mb-1">Rendement net</p>
                        <p class="text-lg font-bold" :class="getYieldColor(rentabilityResult.net_yield)">
                          {{ rentabilityResult.net_yield }}%
                        </p>
                      </div>
                    </div>
                    <div class="space-y-1.5 text-xs">
                      <div class="flex justify-between">
                        <span class="text-gray-500">Cashflow mensuel</span>
                        <span class="font-medium" :class="rentabilityResult.monthly_cashflow >= 0 ? 'text-green-500' : 'text-red-500'">
                          {{ formatCurrency(rentabilityResult.monthly_cashflow) }}
                        </span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-gray-500">Retour sur investissement</span>
                        <span class="font-medium text-black dark:text-white">{{ rentabilityResult.payback_years }} ans</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Save button -->
              <USeparator />
              <UButton block variant="ghost" color="neutral" size="lg" icon="i-lucide-heart">
                Sauvegarder
              </UButton>

              <!-- Quick stats -->
              <div class="pt-2 border-t border-gray-200 dark:border-gray-800 space-y-3 text-sm">
                <div v-if="property.publication_date" class="flex justify-between">
                  <span class="text-gray-500">Publié le</span>
                  <span class="font-medium text-black dark:text-white">
                    {{ new Date(property.publication_date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                  </span>
                </div>
                <div v-if="property.modification_date" class="flex justify-between">
                  <span class="text-gray-500">Modifié le</span>
                  <span class="font-medium text-black dark:text-white">
                    {{ new Date(property.modification_date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
