export default defineNuxtRouteMiddleware(async () => {
  const { isAuthenticated, user, fetchUser } = useAuth()
  const token = useCookie('auth_token')

  if (!isAuthenticated.value && !token.value) {
    return navigateTo('/login')
  }

  if (!user.value) {
    await fetchUser()
  }

  if (user.value?.role !== 'admin') {
    return navigateTo('/dashboard')
  }
})
