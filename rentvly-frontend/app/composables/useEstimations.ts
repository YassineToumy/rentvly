import type { PredictionResult, PropertyForm, RentabilityResult } from './usePrediction'

export interface InvestmentHorizon {
  years: number
  cumulative_income: number
  total_return_pct: number
  annual_net_yield_pct: number
}

export interface InvestmentProjection {
  purchase_price: number
  net_annual_income: number
  net_yield_pct: number
  monthly_cashflow: number
  horizons: InvestmentHorizon[]
}

export type EstimationSaveAction = 'created' | 'already_exists' | 'variant_created' | 'updated'

export interface EstimationVariant {
  id: number
  parent_id: number
  purchase_price: number | null
  predicted_rent: number
  net_yield: number | null
  prediction: PredictionResult
  rentability: RentabilityResult | null
  created_at: string
}

export interface ListingEstimationSummary {
  id: number
  listing_id: string
  purchase_price: number | null
  latest_purchase_price: number | null
  variants_count: number
}

export interface EstimationListItem {
  id: number
  listing_id: string | null
  city: string
  postal_code: string
  district_name: string | null
  property_type: 'flat' | 'house'
  property_type_label: string
  surface_area: number | null
  rooms: number
  predicted_rent: number
  net_yield: number | null
  purchase_price: number | null
  latest_purchase_price?: number | null
  is_purchased: boolean
  purchased_at: string | null
  variants_count?: number
  created_at: string
}

export interface PurchasedEstimation extends EstimationListItem {
  investment: InvestmentProjection | null
}

export interface EstimationDetail extends EstimationListItem {
  form: PropertyForm
  prediction: PredictionResult
  rentability: RentabilityResult | null
  investment?: InvestmentProjection | null
  variants?: EstimationVariant[]
}

export interface DashboardStats {
  total: number
  purchased_count: number
  portfolio_invested: number | null
  average_net_yield: number | null
  cities_count: number
}

export interface SaveEstimationResult {
  data: EstimationDetail
  save_action: EstimationSaveAction
  message?: string
}

export function useEstimations() {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase || 'http://backend.test/api/v1'
  const { authHeaders, isAuthenticated } = useAuth()

  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)
  const stats = ref<DashboardStats>({
    total: 0,
    purchased_count: 0,
    portfolio_invested: null,
    average_net_yield: null,
    cities_count: 0,
  })
  const estimations = ref<EstimationListItem[]>([])
  const purchased = ref<PurchasedEstimation[]>([])

  async function fetchDashboard() {
    if (!isAuthenticated.value) return

    loading.value = true
    error.value = null

    try {
      const res = await $fetch<{
        success: boolean
        data: {
          stats: DashboardStats
          estimations: EstimationListItem[]
          purchased: PurchasedEstimation[]
        }
      }>(`${apiBase}/estimations`, { headers: authHeaders() })

      if (res.success) {
        stats.value = res.data.stats
        estimations.value = res.data.estimations
        purchased.value = res.data.purchased ?? []
      }
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || 'Impossible de charger le tableau de bord.'
    } finally {
      loading.value = false
    }
  }

  async function fetchByListing(listingId: string): Promise<ListingEstimationSummary | null> {
    if (!isAuthenticated.value) return null

    try {
      const res = await $fetch<{ success: boolean; data: ListingEstimationSummary | null }>(
        `${apiBase}/estimations/by-listing/${encodeURIComponent(listingId)}`,
        { headers: authHeaders() }
      )
      return res.success ? res.data : null
    } catch {
      return null
    }
  }

  async function saveEstimation(payload: {
    form: PropertyForm
    prediction: PredictionResult
    rentability?: RentabilityResult | null
    purchase_price?: number | null
    listing_id?: string | null
  }): Promise<SaveEstimationResult | null> {
    if (!isAuthenticated.value) {
      await navigateTo('/login')
      return null
    }

    saving.value = true
    error.value = null

    try {
      const res = await $fetch<{
        success: boolean
        data: EstimationDetail
        save_action?: EstimationSaveAction
        message?: string
      }>(
        `${apiBase}/estimations`,
        {
          method: 'POST',
          headers: authHeaders(),
          body: {
            form: payload.form,
            prediction: payload.prediction,
            rentability: payload.rentability ?? null,
            purchase_price: payload.purchase_price ?? payload.rentability?.purchase_price ?? null,
            listing_id: payload.listing_id ?? null,
          },
        }
      )

      if (res.success) {
        return {
          data: res.data,
          save_action: res.save_action ?? 'created',
          message: res.message,
        }
      }
      return null
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || "Impossible d'enregistrer l'estimation."
      return null
    } finally {
      saving.value = false
    }
  }

  async function fetchEstimation(id: number): Promise<EstimationDetail | null> {
    loading.value = true
    error.value = null

    try {
      const res = await $fetch<{ success: boolean; data: EstimationDetail }>(
        `${apiBase}/estimations/${id}`,
        { headers: authHeaders() }
      )
      if (res.success && res.data) {
        return res.data
      }
      error.value = 'Estimation introuvable.'
      return null
    } catch (e: any) {
      error.value = e?.data?.error || 'Estimation introuvable.'
      return null
    } finally {
      loading.value = false
    }
  }

  async function markAsPurchased(id: number, isPurchased = true): Promise<EstimationDetail | null> {
    saving.value = true
    error.value = null

    try {
      const res = await $fetch<{ success: boolean; data: EstimationDetail; error?: string }>(
        `${apiBase}/estimations/${id}`,
        {
          method: 'PATCH',
          headers: authHeaders(),
          body: { is_purchased: isPurchased },
        }
      )

      if (res.success) {
        return res.data
      }
      return null
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || 'Impossible de mettre à jour le bien.'
      return null
    } finally {
      saving.value = false
    }
  }

  async function deleteEstimation(id: number): Promise<boolean> {
    try {
      await $fetch(`${apiBase}/estimations/${id}`, {
        method: 'DELETE',
        headers: authHeaders(),
      })
      estimations.value = estimations.value.filter(e => e.id !== id)
      purchased.value = purchased.value.filter(e => e.id !== id)
      stats.value.total = estimations.value.length
      stats.value.purchased_count = purchased.value.length
      return true
    } catch {
      return false
    }
  }

  return {
    loading,
    saving,
    error,
    stats,
    estimations,
    purchased,
    fetchDashboard,
    fetchByListing,
    saveEstimation,
    fetchEstimation,
    markAsPurchased,
    deleteEstimation,
  }
}
