export default defineNuxtRouteMiddleware(async () => {
  useHead({
    link: [{ rel: 'icon', type: 'image/x-icon', href: '/logo_rentvly_admin.ico', key: 'favicon' }],
  })

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
