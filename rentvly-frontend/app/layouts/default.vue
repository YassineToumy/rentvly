<script setup lang="ts">
import type { AuthGateReason } from '../composables/useAuthGate'

const route = useRoute()
const { user, isAuthenticated, isAdmin, logout, fetchUser } = useAuth()
const { requireAuth } = useAuthGate()
const colorMode = useColorMode()

useHead({
  link: [{ rel: 'icon', type: 'image/x-icon', href: '/favicon.ico', key: 'favicon' }],
})

onMounted(() => {
  if (isAuthenticated.value && !user.value) fetchUser()
})

type NavItem = {
  label: string
  to: string
  icon: string
  authReason?: AuthGateReason
}

const navItems: NavItem[] = [
  { label: 'Accueil', to: '/', icon: 'i-lucide-home' },
  { label: 'Biens en vente', to: '/listings', icon: 'i-lucide-building' },
  { label: 'Statistiques', to: '/statistiques', icon: 'i-lucide-chart-column', authReason: 'stats' },
  { label: 'Estimer', to: '/predict', icon: 'i-lucide-calculator' },
]

const authNavItems = computed(() => {
  const items: NavItem[] = [
    { label: 'Dashboard', to: '/dashboard', icon: 'i-lucide-layout-dashboard' },
    { label: 'Biens en vente', to: '/listings', icon: 'i-lucide-building' },
    { label: 'Statistiques', to: '/statistiques', icon: 'i-lucide-chart-column' },
    { label: 'Estimer', to: '/predict', icon: 'i-lucide-calculator' },
  ]
  if (isAdmin.value) {
    items.unshift({ label: 'Admin', to: '/admin', icon: 'i-lucide-shield' })
  }
  return items
})

const currentNav = computed(() => isAuthenticated.value ? authNavItems.value : navItems)

const mobileMenuOpen = ref(false)

const userInitial = computed(() => {
  if (user.value?.name) return user.value.name.charAt(0).toUpperCase()
  return 'U'
})

const isLightMode = computed(() => colorMode.preference === 'light')

function toggleColorMode() {
  colorMode.preference = isLightMode.value ? 'dark' : 'light'
}

function onNavClick(event: Event, item: NavItem) {
  if (item.authReason && !requireAuth(item.authReason)) {
    event.preventDefault()
  }
  mobileMenuOpen.value = false
}

function onGuestLink(event: Event, reason: AuthGateReason) {
  if (!requireAuth(reason)) {
    event.preventDefault()
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#e8eaed] dark:bg-gray-950 dark:text-gray-100">
    <header class="app-header backdrop-blur-xl sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <div class="flex items-center gap-8">
          <NuxtLink to="/" class="flex items-center">
            <img
              src="/logo.png"
              alt="Rentvly"
              class="h-10 w-auto object-contain"
            >
          </NuxtLink>

          <nav class="hidden md:flex items-center gap-1">
            <NuxtLink
              v-for="item in currentNav"
              :key="item.to"
              :to="item.to"
              class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
              :class="route.path === item.to ? 'app-nav-active' : 'app-nav-inactive'"
              @click="onNavClick($event, item)"
            >
              <UIcon :name="item.icon" class="size-4" />
              {{ item.label }}
            </NuxtLink>
          </nav>
        </div>

        <div class="hidden md:flex items-center gap-3">
          <UButton
            variant="ghost"
            color="neutral"
            size="sm"
            :icon="isLightMode ? 'i-lucide-moon' : 'i-lucide-sun'"
            @click="toggleColorMode"
          >
            {{ isLightMode ? 'Sombre' : 'Clair' }}
          </UButton>
          <template v-if="!isAuthenticated">
            <UButton to="/login" variant="ghost" color="neutral" size="sm">
              Connexion
            </UButton>
            <UButton to="/register" color="primary" size="sm" class="shadow-lg shadow-primary-500/20">
              Créer un compte
            </UButton>
          </template>
          <template v-else>
            <div class="flex items-center gap-2">
              <UButton
                to="/settings"
                variant="ghost"
                color="neutral"
                size="sm"
                icon="i-lucide-settings"
                aria-label="Paramètres"
              />
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-xs font-bold text-gray-900 dark:text-white overflow-hidden">
                <img
                  v-if="user?.avatar_url"
                  :src="user.avatar_url"
                  alt=""
                  class="size-full object-cover"
                >
                <template v-else>{{ userInitial }}</template>
              </div>
              <UButton variant="ghost" color="neutral" size="sm" icon="i-lucide-log-out" @click="logout">
                Déconnexion
              </UButton>
            </div>
          </template>
        </div>

        <button class="md:hidden p-2 rounded-lg app-nav-inactive" @click="mobileMenuOpen = !mobileMenuOpen">
          <UIcon :name="mobileMenuOpen ? 'i-lucide-x' : 'i-lucide-menu'" class="size-5" />
        </button>
      </div>

      <div v-if="mobileMenuOpen" class="md:hidden app-border border-t bg-[#f4f5f7]/95 dark:bg-gray-950/95 backdrop-blur-xl">
        <div class="px-4 py-4 space-y-1">
          <NuxtLink
            v-for="item in currentNav"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="route.path === item.to ? 'app-nav-active' : 'app-nav-inactive'"
            @click="onNavClick($event, item)"
          >
            <UIcon :name="item.icon" class="size-4" />
            {{ item.label }}
          </NuxtLink>

          <div class="pt-3 app-border border-t space-y-1">
            <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm app-nav-inactive" @click="toggleColorMode">
              <UIcon :name="isLightMode ? 'i-lucide-moon' : 'i-lucide-sun'" class="size-4" />
              {{ isLightMode ? 'Passer en mode sombre' : 'Passer en mode clair' }}
            </button>
            <template v-if="!isAuthenticated">
              <NuxtLink to="/login" class="block px-3 py-2.5 rounded-lg text-sm app-nav-inactive" @click="mobileMenuOpen = false">
                Connexion
              </NuxtLink>
              <NuxtLink to="/register" class="block px-3 py-2.5 rounded-lg text-sm text-emerald-800 dark:text-primary-400 app-surface-hover" @click="mobileMenuOpen = false">
                Créer un compte
              </NuxtLink>
            </template>
            <template v-else>
              <NuxtLink to="/settings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm app-nav-inactive" @click="mobileMenuOpen = false">
                <UIcon name="i-lucide-settings" class="size-4" />
                Paramètres
              </NuxtLink>
              <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm app-nav-inactive" @click="logout(); mobileMenuOpen = false">
                <UIcon name="i-lucide-log-out" class="size-4" />
                Déconnexion
              </button>
            </template>
          </div>
        </div>
      </div>
    </header>

    <main>
      <slot />
    </main>

    <footer class="app-footer mt-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
          <div class="flex items-center">
            <img
              src="/logo.png"
              alt="Rentvly"
              class="h-8 w-auto object-contain"
            >
          </div>
          <div class="flex items-center gap-6 text-sm app-muted font-medium">
            <NuxtLink to="/" class="hover:text-gray-950 dark:hover:text-white transition-colors">Accueil</NuxtLink>
            <NuxtLink
              to="/statistiques"
              class="hover:text-gray-950 dark:hover:text-white transition-colors"
              @click="onGuestLink($event, 'stats')"
            >
              Statistiques
            </NuxtLink>
            <NuxtLink
              to="/predict"
              class="hover:text-gray-950 dark:hover:text-white transition-colors"
            >
              Estimer
            </NuxtLink>
            <span class="text-gray-600 dark:text-gray-500">Données Leboncoin · Modèle CatBoost</span>
          </div>
          <p class="text-sm text-gray-700 dark:text-gray-500">
            © 2026 Rentvly
          </p>
        </div>
      </div>
    </footer>

    <AuthRequiredModal />
  </div>
</template>
