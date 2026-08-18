<script setup lang="ts">
const route = useRoute()
const { user, logout, fetchUser, isAuthenticated } = useAuth()
const colorMode = useColorMode()

onMounted(() => {
  if (isAuthenticated.value && !user.value) fetchUser()
})

const links = [
  { label: 'Vue d\'ensemble', to: '/admin', icon: 'i-lucide-layout-dashboard', exact: true },
  { label: 'Annonces', to: '/admin/ventes', icon: 'i-lucide-building-2' },
  { label: 'Statistiques', to: '/statistiques', icon: 'i-lucide-chart-column' },
  { label: 'Comptes', to: '/admin/users', icon: 'i-lucide-users' },
]

const isLightMode = computed(() => colorMode.preference === 'light')

function toggleColorMode() {
  colorMode.preference = isLightMode.value ? 'dark' : 'light'
}

function isActive(to: string, exact = false) {
  if (exact) return route.path === to
  return route.path === to || route.path.startsWith(`${to}/`)
}

const userInitial = computed(() => user.value?.name?.charAt(0)?.toUpperCase() ?? 'A')
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 flex">
    <aside class="hidden lg:flex w-64 flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
      <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-200 dark:border-slate-800">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center shadow-lg shadow-blue-500/25">
          <UIcon name="i-lucide-shield" class="size-4 text-white" />
        </div>
        <div>
          <p class="text-sm font-semibold tracking-tight">Admin</p>
          <p class="text-[11px] text-slate-500">Rentvly</p>
        </div>
      </div>

      <nav class="flex-1 p-3 space-y-1">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
          :class="isActive(link.to, link.exact)
            ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400'
            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
        >
          <UIcon :name="link.icon" class="size-4" />
          {{ link.label }}
        </NuxtLink>
      </nav>

      <div class="p-3 border-t border-slate-200 dark:border-slate-800 space-y-1">
        <NuxtLink
          to="/dashboard"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
        >
          <UIcon name="i-lucide-arrow-left" class="size-4" />
          Espace investisseur
        </NuxtLink>
        <button
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
          @click="logout"
        >
          <UIcon name="i-lucide-log-out" class="size-4" />
          Déconnexion
        </button>
      </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 px-4 sm:px-6 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl sticky top-0 z-40">
        <div class="flex items-center gap-3">
          <div class="lg:hidden w-9 h-9 rounded-xl bg-blue-500/10 flex items-center justify-center">
            <UIcon name="i-lucide-shield" class="size-4 text-blue-500" />
          </div>
          <div>
            <p class="text-sm font-semibold">Administration</p>
            <p class="text-xs text-slate-500 truncate max-w-[200px] sm:max-w-none">{{ user?.email }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <div class="hidden sm:flex items-center gap-1 mr-2">
            <NuxtLink
              v-for="link in links"
              :key="`m-${link.to}`"
              :to="link.to"
              class="lg:hidden px-2.5 py-1.5 rounded-lg text-xs font-medium"
              :class="isActive(link.to, link.exact) ? 'bg-blue-500/10 text-blue-600' : 'text-slate-500'"
            >
              {{ link.label }}
            </NuxtLink>
          </div>
          <UButton
            variant="ghost"
            color="neutral"
            size="sm"
            :icon="isLightMode ? 'i-lucide-moon' : 'i-lucide-sun'"
            @click="toggleColorMode"
          />
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-xs font-bold text-white">
            {{ userInitial }}
          </div>
        </div>
      </header>

      <main class="flex-1 p-4 sm:p-6 lg:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>
