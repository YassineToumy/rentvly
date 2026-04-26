interface User {
  id: number
  name: string
  email: string
  created_at: string
}

const user = ref<User | null>(null)
const token = ref<string | null>(null)

export function useAuth() {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase || 'http://localhost:8080/api/v1'
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Init from cookie
  if (!token.value) {
    const saved = useCookie('auth_token')
    if (saved.value) token.value = saved.value
  }

  function setAuth(u: User, t: string) {
    user.value = u
    token.value = t
    useCookie('auth_token', { maxAge: 60 * 60 * 24 * 30 }).value = t
  }

  function clearAuth() {
    user.value = null
    token.value = null
    useCookie('auth_token').value = null
  }

  function authHeaders(): Record<string, string> {
    return token.value ? { Authorization: `Bearer ${token.value}` } : {}
  }

  async function register(firstName: string, lastName: string, email: string, password: string, passwordConfirmation: string) {
    loading.value = true
    error.value = null
    try {
      const res = await $fetch<any>(`${apiBase}/register`, {
        method: 'POST',
        body: {
          first_name: firstName,
          last_name: lastName,
          email,
          password,
          password_confirmation: passwordConfirmation,
        },
      })
      if (res.success) {
        setAuth(res.data.user, res.data.token)
        navigateTo('/dashboard')
      }
    } catch (e: any) {
      error.value = e?.data?.message || e?.data?.error || "Erreur lors de l'inscription."
    } finally {
      loading.value = false
    }
  }

  async function login(email: string, password: string) {
    loading.value = true
    error.value = null
    try {
      const res = await $fetch<any>(`${apiBase}/login`, {
        method: 'POST',
        body: { email, password },
      })
      if (res.success) {
        setAuth(res.data.user, res.data.token)
        navigateTo('/dashboard')
      }
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || 'Email ou mot de passe incorrect.'
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await $fetch(`${apiBase}/logout`, {
        method: 'POST',
        headers: authHeaders(),
      })
    } catch {}
    clearAuth()
    navigateTo('/')
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const res = await $fetch<any>(`${apiBase}/me`, {
        headers: authHeaders(),
      })
      if (res.success) user.value = res.data
    } catch {
      clearAuth()
    }
  }

  const isAuthenticated = computed(() => !!token.value)

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    register,
    login,
    logout,
    fetchUser,
    authHeaders,
  }
}