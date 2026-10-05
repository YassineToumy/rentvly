interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'investor'
  avatar?: string | null
  avatar_url?: string | null
  created_at: string
}

const user = ref<User | null>(null)
const token = ref<string | null>(null)

export function useAuth() {
  const apiBase = useApiBase()
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Init from cookie
  if (!token.value) {
    const saved = useCookie('auth_token')
    if (saved.value) token.value = saved.value
  }

  async function fetchUser() {
    if (!token.value) return
    try {
      const res = await $fetch<any>(`${apiBase}/me`, {
        headers: authHeaders(),
      })
      if (res.success) user.value = normalizeUser(res.data)
    } catch {
      clearAuth()
    }
  }

  function normalizeUser(u: User): User {
    if (!u) return u
    if (u.avatar_url) return u
    if (u.avatar) {
      const origin = apiBase.replace(/\/api\/v1\/?$/, '')
      return { ...u, avatar_url: `${origin}/${u.avatar.replace(/^\//, '')}` }
    }
    return u
  }

  function setAuth(u: User, t: string) {
    user.value = normalizeUser(u)
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

  function postAuthRedirect(role?: string) {
    const route = useRoute()
    const redirect = route.query.redirect
    if (typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//')) {
      return navigateTo(redirect)
    }
    return navigateTo(role === 'admin' ? '/admin' : '/dashboard')
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
      if (res.requires_verification) {
        await navigateTo(`/verify-email?email=${encodeURIComponent(res.email ?? email)}`)
        return
      }
      if (res.success) {
        setAuth(res.data.user, res.data.token)
        await postAuthRedirect(res.data.user?.role)
      }
    } catch (e: any) {
      error.value = e?.data?.message || e?.data?.error || "Erreur lors de l'inscription."
    } finally {
      loading.value = false
    }
  }

  async function verifyEmail(email: string, code: string) {
    loading.value = true
    error.value = null
    try {
      const res = await $fetch<any>(`${apiBase}/verify-email`, {
        method: 'POST',
        body: { email, code },
      })
      if (res.success) {
        setAuth(res.data.user, res.data.token)
        await postAuthRedirect(res.data.user?.role)
        return true
      }
      error.value = res.error || 'Code invalide ou expiré.'
      return false
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || 'Code invalide ou expiré.'
      return false
    } finally {
      loading.value = false
    }
  }

  async function resendVerificationCode(email: string) {
    error.value = null
    try {
      await $fetch<any>(`${apiBase}/resend-verification-code`, {
        method: 'POST',
        body: { email },
      })
      return true
    } catch (e: any) {
      error.value = e?.data?.error || e?.data?.message || "Impossible d'envoyer le code."
      return false
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
        await postAuthRedirect(res.data.user?.role)
      }
    } catch (e: any) {
      // Compte non vérifié : rediriger vers la vérification 2FA
      if (e?.data?.requires_verification) {
        await navigateTo(`/verify-email?email=${encodeURIComponent(e.data.email ?? email)}`)
        return
      }
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

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    register,
    verifyEmail,
    resendVerificationCode,
    login,
    logout,
    fetchUser,
    authHeaders,
  }
}