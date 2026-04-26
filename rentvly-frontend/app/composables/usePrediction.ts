// composables/usePrediction.ts

export interface PropertyForm {
  // Location
  city: string
  postal_code: string
  district_name: string
  property_type: 'flat' | 'house'

  // Characteristics
  surface_area: number | null
  rooms: number
  bedrooms: number
  bathrooms: number
  shower_rooms: number
  toilets: number
  floor: number
  total_floors: number
  land_surface: number

  // State
  year_built: number
  is_new: boolean
  is_furnished: boolean

  // Equipment
  has_cellar: boolean
  has_balcony: boolean
  has_terrace: boolean
  has_garden: boolean
  has_pool: boolean
  has_elevator: boolean
  has_intercom: boolean
  has_air_conditioning: boolean
  has_fireplace: boolean
  has_separate_toilet: boolean

  // Energy
  energy_class: string
  energy_value: number
  greenhouse_value: number
  heating_type: string

  // Parking
  parking_places: number
  garages: number

  // Charges
  charges: number
}

export interface PredictionResult {
  predicted_rent: number
  confidence_range: {
    low: number
    high: number
    mape_pct: number
  }
  model_metrics: {
    mae: number
    r2: number
    mape: number
  }
}

export interface RentabilityResult {
  predicted_rent: number
  purchase_price: number
  annual_rent: number
  gross_yield: number
  net_yield: number
  monthly_charges: number
  monthly_cashflow: number
  payback_years: number
}

export function usePrediction() {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase || 'http://localhost:8080/api/v1'

  const loading = ref(false)
  const error = ref<string | null>(null)
  const predictionResult = ref<PredictionResult | null>(null)
  const rentabilityResult = ref<RentabilityResult | null>(null)

  const defaultForm: PropertyForm = {
    city: '',
    postal_code: '',
    district_name: '',
    property_type: 'flat',
    surface_area: null,
    rooms: 1,
    bedrooms: 0,
    bathrooms: 0,
    shower_rooms: 0,
    toilets: 0,
    floor: 0,
    total_floors: 0,
    land_surface: 0,
    year_built: 2000,
    is_new: false,
    is_furnished: false,
    has_cellar: false,
    has_balcony: false,
    has_terrace: false,
    has_garden: false,
    has_pool: false,
    has_elevator: false,
    has_intercom: false,
    has_air_conditioning: false,
    has_fireplace: false,
    has_separate_toilet: false,
    energy_class: 'D',
    energy_value: 0,
    greenhouse_value: 0,
    heating_type: 'individual',
    parking_places: 0,
    garages: 0,
    charges: 0,
  }

  const form = reactive<PropertyForm>({ ...defaultForm })

  /**
   * POST /api/v1/predict
   * Sends the form as-is (snake_case). Laravel maps it to model fields.
   */
  async function predictRent() {
    loading.value = true
    error.value = null
    predictionResult.value = null

    try {
      const res = await $fetch<{ success: boolean; data: PredictionResult; error?: string }>(
        `${apiBase}/predict`,
        {
          method: 'POST',
          body: { ...form },
        }
      )

      if (res?.success) {
        predictionResult.value = res.data
      } else {
        throw new Error(res?.error || 'Prediction failed')
      }
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || e.message || 'An error occurred'
    } finally {
      loading.value = false
    }
  }

  async function calculateRentability(purchasePrice: number) {
    loading.value = true
    error.value = null
    rentabilityResult.value = null

    try {
      const res = await $fetch<{ success: boolean; data: RentabilityResult }>(
        `${apiBase}/rentability`,
        {
          method: 'POST',
          body: {
            purchase_price: purchasePrice,
            property: { ...form },
          },
        }
      )

      if (res?.success) {
        rentabilityResult.value = res.data
      } else {
        throw new Error('Rentability calculation failed')
      }
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || e.message || 'An error occurred'
    } finally {
      loading.value = false
    }
  }

  function resetForm() {
    Object.assign(form, defaultForm)
    predictionResult.value = null
    rentabilityResult.value = null
    error.value = null
  }

  return {
    form,
    loading,
    error,
    predictionResult,
    rentabilityResult,
    predictRent,
    calculateRentability,
    resetForm,
  }
}