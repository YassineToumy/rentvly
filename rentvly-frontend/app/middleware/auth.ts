export default defineNuxtRouteMiddleware(() => {
  const { isAuthenticated } = useAuth()
  const token = useCookie('auth_token')

  if (!isAuthenticated.value && !token.value) {
    return navigateTo('/login')
  }
})
