export type AdminStats = {
  users_total: number
  users_admins: number
  users_investors: number
  ventes_total: number
  estimations_total: number
  purchased_total: number
  ventes_avg_price: number
  recent_users: Array<{
    id: number
    name: string
    email: string
    role: string
    created_at: string
  }>
  recent_ventes: Array<{
    id: number
    title: string | null
    city: string | null
    price: number | null
    property_type: string | null
    publication_date: string | null
  }>
}

export type AdminUser = {
  id: number
  name: string
  email: string
  role: 'admin' | 'investor'
  estimations_count: number
  created_at: string | null
  updated_at: string | null
}

export type AdminVente = {
  id: number
  external_id?: string | null
  title: string | null
  description?: string | null
  city: string | null
  postal_code: string | null
  property_type: string | null
  price: number | null
  price_per_sqm?: number | null
  surface_area: number | null
  rooms_quantity: number | null
  department_code?: string | null
  district_name?: string | null
  latitude?: number | null
  longitude?: number | null
  owner_name?: string | null
  owner_type?: string | null
  is_new_property?: boolean
  is_pro?: boolean
  photos?: string[]
  image?: string | null
  photos_count?: number
  publication_date?: string | null
  created_at?: string | null
  updated_at?: string | null
}

type PageMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export function useAdmin() {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase || 'http://backend.test/api/v1'
  const { authHeaders } = useAuth()

  const loading = ref(false)
  const error = ref<string | null>(null)

  async function api<T>(path: string, options: Record<string, any> = {}): Promise<T> {
    loading.value = true
    error.value = null
    try {
      return await $fetch<T>(`${apiBase}/admin${path}`, {
        ...options,
        headers: {
          ...authHeaders(),
          ...(options.headers || {}),
        },
      })
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || 'Une erreur est survenue.'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchStats() {
    const res = await api<{ success: boolean; data: AdminStats }>('/stats')
    return res.data
  }

  async function fetchUsers(params: Record<string, any> = {}) {
    const res = await api<{ success: boolean; data: AdminUser[]; meta: PageMeta }>('/users', {
      query: params,
    })
    return { data: res.data, meta: res.meta }
  }

  async function createUser(body: Record<string, any>) {
    const res = await api<{ success: boolean; data: AdminUser }>('/users', {
      method: 'POST',
      body,
    })
    return res.data
  }

  async function updateUser(id: number, body: Record<string, any>) {
    const res = await api<{ success: boolean; data: AdminUser }>(`/users/${id}`, {
      method: 'PATCH',
      body,
    })
    return res.data
  }

  async function deleteUser(id: number) {
    return api<{ success: boolean; message: string }>(`/users/${id}`, {
      method: 'DELETE',
    })
  }

  async function fetchVentes(params: Record<string, any> = {}) {
    const res = await api<{ success: boolean; data: AdminVente[]; meta: PageMeta }>('/ventes', {
      query: params,
    })
    return { data: res.data, meta: res.meta }
  }

  async function fetchVente(id: number) {
    const res = await api<{ success: boolean; data: AdminVente }>(`/ventes/${id}`)
    return res.data
  }

  async function createVente(body: Record<string, any>) {
    const res = await api<{ success: boolean; data: AdminVente }>('/ventes', {
      method: 'POST',
      body,
    })
    return res.data
  }

  async function updateVente(id: number, body: Record<string, any>) {
    const res = await api<{ success: boolean; data: AdminVente }>(`/ventes/${id}`, {
      method: 'PATCH',
      body,
    })
    return res.data
  }

  async function deleteVente(id: number) {
    return api<{ success: boolean; message: string }>(`/ventes/${id}`, {
      method: 'DELETE',
    })
  }

  return {
    loading,
    error,
    fetchStats,
    fetchUsers,
    createUser,
    updateUser,
    deleteUser,
    fetchVentes,
    fetchVente,
    createVente,
    updateVente,
    deleteVente,
  }
}
