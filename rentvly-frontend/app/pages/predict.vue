<script setup lang="ts">
import { ref, watch } from 'vue'
import { usePrediction } from '../composables/usePrediction'
import type { PropertyForm } from '../composables/usePrediction'

const DRAFT_KEY = 'rentvly_predict_draft'

type PredictDraft = {
  form: PropertyForm
  purchasePrice: number | null
  currentStep: number
  pendingPredict: boolean
}

const { form, loading, error, predictionResult, rentabilityResult, predictRent, calculateRentability, resetForm } = usePrediction()
const { isAuthenticated } = useAuth()
const { requireAuth } = useAuthGate()
const { saveEstimation, saving: savingEstimation } = useEstimations()

const purchasePrice = ref<number | null>(null)
const showResults = ref(false)
const saveSuccess = ref(false)

const currentStep = ref(0)
const steps = [
  { label: 'Localisation', icon: 'i-lucide-map-pin' },
  { label: 'Caractéristiques', icon: 'i-lucide-ruler' },
  { label: 'Équipements', icon: 'i-lucide-settings' },
  { label: 'Énergie', icon: 'i-lucide-zap' },
]

const propertyTypes = [
  { label: 'Appartement', value: 'flat' },
  { label: 'Maison', value: 'house' },
]

const energyClasses = [
  { label: 'A – Excellent', value: 'A' },
  { label: 'B – Très bon', value: 'B' },
  { label: 'C – Bon', value: 'C' },
  { label: 'D – Moyen', value: 'D' },
  { label: 'E – Passable', value: 'E' },
  { label: 'F – Mauvais', value: 'F' },
  { label: 'G – Très mauvais', value: 'G' },
]

const heatingTypes = [
  { label: 'Individuel', value: 'individual' },
  { label: 'Collectif', value: 'collective' },
  { label: 'Autre', value: 'other' },
]

watch(() => form.postal_code, (val) => {
  if (val && val.length >= 2) {
  }
})

function nextStep() {
  if (currentStep.value < steps.length - 1) currentStep.value++
}

function prevStep() {
  if (currentStep.value > 0) currentStep.value--
}

function saveDraft(pendingPredict = true) {
  if (!import.meta.client) return
  const draft: PredictDraft = {
    form: { ...form },
    purchasePrice: purchasePrice.value,
    currentStep: currentStep.value,
    pendingPredict,
  }
  sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft))
}

function clearDraft() {
  if (!import.meta.client) return
  sessionStorage.removeItem(DRAFT_KEY)
}

function restoreDraft(): PredictDraft | null {
  if (!import.meta.client) return null
  try {
    const raw = sessionStorage.getItem(DRAFT_KEY)
    if (!raw) return null
    return JSON.parse(raw) as PredictDraft
  } catch {
    return null
  }
}

async function runPredict() {
  await predictRent()
  if (predictionResult.value) {
    showResults.value = true
  }
}

async function handlePredict() {
  if (!isAuthenticated.value) {
    saveDraft(true)
    requireAuth('predict')
    return
  }
  clearDraft()
  await runPredict()
}

async function handleRentability() {
  if (!requireAuth('predict')) return
  if (!purchasePrice.value || purchasePrice.value <= 0) return
  await calculateRentability(purchasePrice.value)
}

async function handleSave() {
  if (!predictionResult.value) return
  if (!requireAuth('predict')) return

  saveSuccess.value = false
  const saved = await saveEstimation({
    form: { ...form },
    prediction: predictionResult.value,
    rentability: rentabilityResult.value,
    purchase_price: purchasePrice.value,
  })

  if (saved) {
    saveSuccess.value = true
    clearDraft()
    await navigateTo(`/dashboard/estimations/${saved.data.id}`)
  }
}

async function resumePendingPredict() {
  if (!isAuthenticated.value) return
  const draft = restoreDraft()
  if (!draft?.pendingPredict) return

  Object.assign(form, draft.form)
  purchasePrice.value = draft.purchasePrice
  currentStep.value = draft.currentStep ?? 0
  clearDraft()
  await runPredict()
}

onMounted(() => {
  resumePendingPredict()
})

watch(isAuthenticated, (ok) => {
  if (ok) resumePendingPredict()
})

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 0 }).format(value)
}

function getYieldColor(yieldPct: number): string {
  if (yieldPct >= 7) return 'text-emerald-800 dark:text-green-400'
  if (yieldPct >= 5) return 'text-emerald-700 dark:text-emerald-400'
  if (yieldPct >= 3) return 'text-amber-700 dark:text-yellow-500'
  return 'text-red-700 dark:text-red-500'
}
</script>

<template>
  <div class="min-h-screen bg-[#e8eaed] dark:bg-gray-950">
    <div class="bg-[#f4f5f7] dark:bg-gray-900 border-b border-gray-300/80 dark:border-gray-800 shadow-[0_2px_10px_rgba(15,23,42,0.06)] dark:shadow-none">
      <div class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-950 dark:text-white">
          Estimateur de Loyer
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-500 dark:text-gray-400">
          Estimez le loyer mensuel de votre bien immobilier grâce à notre modèle de prédiction.
        </p>
        <p
          v-if="!isAuthenticated"
          class="mt-3 inline-flex items-center gap-2 rounded-lg bg-emerald-100 dark:bg-primary-950/40 px-3 py-1.5 text-xs font-medium text-emerald-900 dark:text-primary-300 ring-1 ring-emerald-700/20"
        >
          <UIcon name="i-lucide-info" class="size-3.5" />
          Remplissez le formulaire librement — la connexion sera demandée au moment d’estimer.
        </p>
      </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2">
          <UCard class="shadow-[0_4px_16px_rgba(15,23,42,0.1)] dark:shadow-none ring-1 ring-gray-300/80 dark:ring-gray-800">
            <div class="flex items-center justify-between mb-8">
              <button
                v-for="(step, i) in steps"
                :key="i"
                class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors"
                :class="i === currentStep
                  ? 'bg-emerald-100 dark:bg-primary-950 text-emerald-900 dark:text-primary-400'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-600'"
                @click="currentStep = i"
              >
                <UIcon :name="step.icon" class="size-5" />
                <span class="text-sm font-medium hidden sm:inline">{{ step.label }}</span>
                <span class="text-sm font-medium sm:hidden">{{ i + 1 }}</span>
              </button>
            </div>

            <div v-show="currentStep === 0" class="space-y-5">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Ville" required>
                  <UInput v-model="form.city" placeholder="Paris, Lyon, Marseille..." icon="i-lucide-map-pin" />
                </UFormField>

                <UFormField label="Code postal" required>
                  <UInput v-model="form.postal_code" placeholder="75011" icon="i-lucide-mail" maxlength="5" />
                </UFormField>
              </div>

              <UFormField label="Quartier">
                <UInput v-model="form.district_name" placeholder="Nom du quartier (optionnel)" icon="i-lucide-navigation" />
              </UFormField>

              <UFormField label="Type de bien" required>
                <USelect v-model="form.property_type" :items="propertyTypes" value-key="value" />
              </UFormField>
            </div>

            <div v-show="currentStep === 1" class="space-y-5">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Surface habitable (m²)" required>
                  <UInput v-model.number="form.surface_area" type="number" placeholder="45" icon="i-lucide-ruler" />
                </UFormField>

                <UFormField label="Nombre de pièces" required>
                  <UInput v-model.number="form.rooms" type="number" min="1" placeholder="2" icon="i-lucide-layout-grid" />
                </UFormField>

                <UFormField label="Chambres">
                  <UInput v-model.number="form.bedrooms" type="number" min="0" placeholder="1" icon="i-lucide-bed" />
                </UFormField>

                <UFormField label="Salles de bain">
                  <UInput v-model.number="form.bathrooms" type="number" min="0" placeholder="1" icon="i-lucide-bath" />
                </UFormField>

                <UFormField label="Salles d'eau">
                  <UInput v-model.number="form.shower_rooms" type="number" min="0" placeholder="0" />
                </UFormField>

                <UFormField label="WC">
                  <UInput v-model.number="form.toilets" type="number" min="0" placeholder="1" />
                </UFormField>

                <UFormField label="Étage">
                  <UInput v-model.number="form.floor" type="number" min="0" placeholder="3" icon="i-lucide-layers" />
                </UFormField>

                <UFormField label="Nombre d'étages total">
                  <UInput v-model.number="form.total_floors" type="number" min="0" placeholder="5" />
                </UFormField>

                <UFormField label="Année de construction">
                  <UInput v-model.number="form.year_built" type="number" min="1800" max="2026" placeholder="1990" icon="i-lucide-calendar" />
                </UFormField>

                <UFormField label="Charges mensuelles (€)">
                  <UInput v-model.number="form.charges" type="number" min="0" placeholder="50" icon="i-lucide-euro" />
                </UFormField>
              </div>

              <div v-if="form.property_type === 'house'">
                <UFormField label="Surface terrain (m²)">
                  <UInput v-model.number="form.land_surface" type="number" min="0" placeholder="200" icon="i-lucide-trees" />
                </UFormField>
              </div>

              <div class="flex gap-4">
                <UCheckbox v-model="form.is_furnished" label="Meublé" />
                <UCheckbox v-model="form.is_new" label="Neuf" />
              </div>
            </div>

            <div v-show="currentStep === 2" class="space-y-6">
              <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Extérieur</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  <UCheckbox v-model="form.has_balcony" label="Balcon" />
                  <UCheckbox v-model="form.has_terrace" label="Terrasse" />
                  <UCheckbox v-model="form.has_garden" label="Jardin" />
                  <UCheckbox v-model="form.has_pool" label="Piscine" />
                  <UCheckbox v-model="form.has_cellar" label="Cave" />
                </div>
              </div>

              <USeparator />

              <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Intérieur</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  <UCheckbox v-model="form.has_elevator" label="Ascenseur" />
                  <UCheckbox v-model="form.has_intercom" label="Interphone" />
                  <UCheckbox v-model="form.has_air_conditioning" label="Climatisation" />
                  <UCheckbox v-model="form.has_fireplace" label="Cheminée" />
                  <UCheckbox v-model="form.has_separate_toilet" label="WC séparés" />
                </div>
              </div>

              <USeparator />

              <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Stationnement</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <UFormField label="Places de parking">
                    <UInput v-model.number="form.parking_places" type="number" min="0" placeholder="0" icon="i-lucide-car" />
                  </UFormField>
                  <UFormField label="Garages">
                    <UInput v-model.number="form.garages" type="number" min="0" placeholder="0" />
                  </UFormField>
                </div>
              </div>
            </div>

            <div v-show="currentStep === 3" class="space-y-5">
              <UFormField label="Classe énergétique (DPE)">
                <USelect v-model="form.energy_class" :items="energyClasses" value-key="value" />
              </UFormField>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <UFormField label="Consommation énergie (kWh/m²/an)">
                  <UInput v-model.number="form.energy_value" type="number" min="0" placeholder="210" icon="i-lucide-flame" />
                </UFormField>

                <UFormField label="Émissions GES (kgCO₂/m²/an)">
                  <UInput v-model.number="form.greenhouse_value" type="number" min="0" placeholder="30" icon="i-lucide-cloud" />
                </UFormField>
              </div>

              <UFormField label="Type de chauffage">
                <USelect v-model="form.heating_type" :items="heatingTypes" value-key="value" />
              </UFormField>
            </div>

            <div class="flex justify-between mt-8 pt-6 border-t border-gray-200 dark:border-gray-800">
              <UButton
                v-if="currentStep > 0"
                variant="ghost"
                icon="i-lucide-arrow-left"
                @click="prevStep"
              >
                Précédent
              </UButton>
              <div v-else />

              <div class="flex gap-3">
                <UButton
                  v-if="currentStep < steps.length - 1"
                  trailing-icon="i-lucide-arrow-right"
                  @click="nextStep"
                >
                  Suivant
                </UButton>

                <UButton
                  v-if="currentStep === steps.length - 1"
                  color="primary"
                  icon="i-lucide-calculator"
                  :loading="loading"
                  :disabled="!form.city || !form.postal_code || !form.surface_area"
                  @click="handlePredict"
                >
                  Estimer le loyer
                </UButton>
              </div>
            </div>
          </UCard>
        </div>

        <div class="space-y-6">

          <UAlert
            v-if="error"
            color="error"
            icon="i-lucide-alert-circle"
            title="Erreur"
            :description="error"
          />

          <UCard
            v-if="predictionResult"
            class="shadow-[0_4px_16px_rgba(15,23,42,0.1)] dark:shadow-none ring-1 ring-gray-300/80 dark:ring-gray-800"
          >
            <div class="text-center">
              <p class="text-sm text-gray-600 dark:text-gray-500 dark:text-gray-400 mb-1">Loyer mensuel estimé</p>
              <p class="text-4xl font-bold text-emerald-900 dark:text-primary-400">
                {{ formatCurrency(predictionResult.predicted_rent) }}
              </p>
              <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                {{ formatCurrency(predictionResult.confidence_range.low) }}
                –
                {{ formatCurrency(predictionResult.confidence_range.high) }}
              </p>
              <p class="text-xs text-gray-600 dark:text-gray-400">
                Marge d'erreur: ±{{ predictionResult.confidence_range.mape_pct }}%
              </p>
            </div>

            <USeparator class="my-4" />

            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-500">Loyer annuel</span>
                <span class="font-medium">{{ formatCurrency(predictionResult.predicted_rent * 12) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-500">Prix au m²/mois</span>
                <span class="font-medium">
                  {{ form.surface_area ? formatCurrency(predictionResult.predicted_rent / form.surface_area) : '–' }}/m²
                </span>
              </div>
            </div>
          </UCard>

          <UCard
            v-if="!predictionResult && !error"
            class="shadow-[0_4px_16px_rgba(15,23,42,0.1)] dark:shadow-none ring-1 ring-gray-300/80 dark:ring-gray-800"
          >
            <div class="text-center py-6">
              <UIcon name="i-lucide-home" class="size-12 text-gray-700 dark:text-gray-300 dark:text-gray-600 mx-auto mb-3" />
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Remplissez le formulaire et cliquez sur "Estimer le loyer"
              </p>
            </div>
          </UCard>

          <UCard
            v-if="predictionResult"
            class="shadow-[0_4px_16px_rgba(15,23,42,0.1)] dark:shadow-none ring-1 ring-gray-300/80 dark:ring-gray-800"
          >
            <template #header>
              <div class="flex items-center gap-2">
                <UIcon name="i-lucide-trending-up" class="size-5 text-emerald-800 dark:text-primary-400" />
                <h3 class="font-semibold">Rentabilité locative</h3>
              </div>
            </template>

            <div class="space-y-4">
              <UFormField label="Prix d'achat du bien (€)">
                <UInput
                  v-model.number="purchasePrice"
                  type="number"
                  min="1000"
                  placeholder="250000"
                  icon="i-lucide-euro"
                />
              </UFormField>

              <UButton
                block
                color="primary"
                variant="soft"
                icon="i-lucide-bar-chart"
                :loading="loading"
                :disabled="!purchasePrice || purchasePrice <= 0"
                @click="handleRentability"
              >
                Calculer la rentabilité
              </UButton>
            </div>

            <!-- Rentability results -->
            <div v-if="rentabilityResult" class="mt-6 space-y-4">
              <USeparator />

              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <p class="text-xs text-gray-600 dark:text-gray-500 mb-1">Rendement brut</p>
                  <p class="text-xl font-bold" :class="getYieldColor(rentabilityResult.gross_yield)">
                    {{ rentabilityResult.gross_yield }}%
                  </p>
                </div>
                <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <p class="text-xs text-gray-600 dark:text-gray-500 mb-1">Rendement net</p>
                  <p class="text-xl font-bold" :class="getYieldColor(rentabilityResult.net_yield)">
                    {{ rentabilityResult.net_yield }}%
                  </p>
                </div>
              </div>

              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-500">Loyer annuel</span>
                  <span class="font-medium">{{ formatCurrency(rentabilityResult.annual_rent) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-500">Cashflow mensuel net</span>
                  <span class="font-medium" :class="rentabilityResult.monthly_cashflow >= 0 ? 'text-emerald-800 dark:text-green-400' : 'text-red-700 dark:text-red-500'">
                    {{ formatCurrency(rentabilityResult.monthly_cashflow) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-500">Retour sur investissement</span>
                  <span class="font-medium">{{ rentabilityResult.payback_years }} ans</span>
                </div>
              </div>
            </div>
          </UCard>

          <UButton
            v-if="predictionResult"
            block
            color="primary"
            icon="i-lucide-bookmark"
            :loading="savingEstimation"
            @click="handleSave"
          >
            {{ isAuthenticated ? 'Enregistrer dans mon tableau de bord' : 'Se connecter pour enregistrer' }}
          </UButton>

          <UButton
            v-if="predictionResult"
            block
            variant="ghost"
            color="neutral"
            icon="i-lucide-rotate-ccw"
            @click="resetForm(); showResults = false; saveSuccess = false"
          >
            Nouvelle estimation
          </UButton>
        </div>
      </div>
    </div>
  </div>
</template>
