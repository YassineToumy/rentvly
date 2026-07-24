<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const colorMode = useColorMode()

const themeOptions = [
  { value: 'light', label: 'Clair', icon: 'i-lucide-sun', description: 'Fond blanc, interface lumineuse' },
  { value: 'dark', label: 'Sombre', icon: 'i-lucide-moon', description: 'Thème actuel de Rentvly' },
] as const

function setTheme(value: 'light' | 'dark') {
  colorMode.preference = value
}
</script>

<template>
  <div class="app-page">
    <div class="app-border border-b">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
        <NuxtLink
          to="/dashboard"
          class="inline-flex items-center gap-2 text-sm app-subtle hover:opacity-80 transition-opacity mb-4"
        >
          <UIcon name="i-lucide-arrow-left" class="size-4" />
          Retour au tableau de bord
        </NuxtLink>
        <h1 class="text-2xl font-bold app-heading">
          Paramètres
        </h1>
        <p class="mt-1 text-sm app-muted">
          Personnalisez votre expérience Rentvly.
        </p>
      </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
      <section class="app-card p-6">
        <div class="flex items-start gap-3 mb-6">
          <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
            <UIcon name="i-lucide-palette" class="size-5 text-primary-500" />
          </div>
          <div>
            <h2 class="text-lg font-semibold app-heading">
              Apparence
            </h2>
            <p class="text-sm app-muted mt-0.5">
              Choisissez le mode d'affichage de toute l'application.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <button
            v-for="option in themeOptions"
            :key="option.value"
            type="button"
            class="flex items-start gap-4 p-4 rounded-xl border-2 text-left transition-all"
            :class="colorMode.preference === option.value
              ? 'border-primary-500 bg-primary-500/5'
              : 'app-border border app-surface-hover'"
            @click="setTheme(option.value)"
          >
            <div
              class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
              :class="option.value === 'light' ? 'bg-white border border-gray-200' : 'bg-gray-900 border border-gray-700'"
            >
              <UIcon
                :name="option.icon"
                class="size-5"
                :class="option.value === 'light' ? 'text-amber-500' : 'text-primary-400'"
              />
            </div>
            <div class="min-w-0">
              <p class="font-medium app-heading">
                {{ option.label }}
              </p>
              <p class="text-xs app-muted mt-1">
                {{ option.description }}
              </p>
            </div>
            <UIcon
              v-if="colorMode.preference === option.value"
              name="i-lucide-check-circle-2"
              class="size-5 text-primary-500 ml-auto flex-shrink-0"
            />
          </button>
        </div>
      </section>
    </div>
  </div>
</template>
