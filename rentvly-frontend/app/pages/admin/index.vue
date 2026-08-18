<script setup lang="ts">
import type { AdminStats } from '~/composables/useAdmin'

const { fetchStats, loading, error } = useAdmin()
const stats = ref<AdminStats | null>(null)

onMounted(async () => {
  try {
    stats.value = await fetchStats()
  } catch {}
})

function formatCurrency(value: number) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
  }).format(value)
}

function formatDate(date: string | null) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

const cards = computed(() => {
  if (!stats.value) return []
  return [
    { label: 'Comptes', value: String(stats.value.users_total), hint: `${stats.value.users_admins} admin · ${stats.value.users_investors} investisseurs`, icon: 'i-lucide-users', to: '/admin/users' },
    { label: 'Annonces', value: String(stats.value.ventes_total), hint: 'Biens publiés sur le site', icon: 'i-lucide-building-2', to: '/admin/ventes' },
    { label: 'Estimations', value: String(stats.value.estimations_total), hint: `${stats.value.purchased_total} marquées achetées`, icon: 'i-lucide-calculator', to: '/admin' },
    { label: 'Prix moyen', value: formatCurrency(stats.value.ventes_avg_price), hint: 'Moyenne des annonces', icon: 'i-lucide-badge-euro', to: '/admin/ventes' },
  ]
})
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Vue d'ensemble</h1>
        <p class="text-sm text-slate-500 mt-1">Gérez les annonces et les comptes de la plateforme.</p>
      </div>
      <div class="flex gap-2">
        <UButton to="/admin/ventes" color="blue" icon="i-lucide-plus">
          Nouvelle annonce
        </UButton>
        <UButton to="/admin/users" variant="soft" color="blue" icon="i-lucide-user-plus">
          Nouveau compte
        </UButton>
      </div>
    </div>

    <div v-if="loading && !stats" class="flex justify-center py-20">
      <UIcon name="i-lucide-loader-2" class="size-8 text-blue-500 animate-spin" />
    </div>

    <UAlert v-else-if="error" color="error" variant="soft" :title="error" />

    <template v-else-if="stats">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <NuxtLink
          v-for="card in cards"
          :key="card.label"
          :to="card.to"
          class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 hover:border-blue-300 dark:hover:border-blue-700 transition-colors"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
              <UIcon :name="card.icon" class="size-5 text-blue-500" />
            </div>
          </div>
          <p class="text-2xl font-semibold tracking-tight">{{ card.value }}</p>
          <p class="text-sm font-medium mt-1">{{ card.label }}</p>
          <p class="text-xs text-slate-500 mt-1">{{ card.hint }}</p>
        </NuxtLink>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold">Comptes récents</h2>
            <UButton to="/admin/users" variant="ghost" color="blue" size="xs">Voir tout</UButton>
          </div>
          <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <li
              v-for="u in stats.recent_users"
              :key="u.id"
              class="px-5 py-3.5 flex items-center justify-between gap-3"
            >
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ u.name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ u.email }}</p>
              </div>
              <div class="text-right shrink-0">
                <span
                  class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide"
                  :class="u.role === 'admin'
                    ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400'
                    : 'bg-slate-500/10 text-slate-600 dark:text-slate-400'"
                >
                  {{ u.role }}
                </span>
                <p class="text-[11px] text-slate-500 mt-1">{{ formatDate(u.created_at) }}</p>
              </div>
            </li>
            <li v-if="!stats.recent_users.length" class="px-5 py-8 text-center text-sm text-slate-500">
              Aucun compte
            </li>
          </ul>
        </section>

        <section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold">Annonces récentes</h2>
            <UButton to="/admin/ventes" variant="ghost" color="blue" size="xs">Voir tout</UButton>
          </div>
          <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <li
              v-for="v in stats.recent_ventes"
              :key="v.id"
              class="px-5 py-3.5 flex items-center justify-between gap-3"
            >
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ v.title || 'Sans titre' }}</p>
                <p class="text-xs text-slate-500 truncate">{{ v.city }} · {{ v.property_type }}</p>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                  {{ v.price != null ? formatCurrency(v.price) : '—' }}
                </p>
                <p class="text-[11px] text-slate-500 mt-1">{{ formatDate(v.publication_date) }}</p>
              </div>
            </li>
            <li v-if="!stats.recent_ventes.length" class="px-5 py-8 text-center text-sm text-slate-500">
              Aucune annonce
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>
