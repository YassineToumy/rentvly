<script setup lang="ts">
definePageMeta({ layout: false })

const { register, loading, error, isAuthenticated } = useAuth()

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const acceptTerms = ref(false)

if (isAuthenticated.value) navigateTo('/dashboard')

const passwordStrength = computed(() => {
  const p = password.value
  if (!p) return { score: 0, label: '', color: '' }
  let score = 0
  if (p.length >= 8) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  const levels = [
    { score: 0, label: '', color: '' },
    { score: 1, label: 'Faible', color: 'bg-red-500' },
    { score: 2, label: 'Moyen', color: 'bg-yellow-500' },
    { score: 3, label: 'Bon', color: 'bg-emerald-500' },
    { score: 4, label: 'Excellent', color: 'bg-green-400' },
  ]
  return levels[score]
})

const passwordsMatch = computed(() => {
  if (!confirmPassword.value) return null
  return password.value === confirmPassword.value
})

async function handleRegister() {
  if (!acceptTerms.value) return
  if (password.value !== confirmPassword.value) return
  await register(firstName.value, lastName.value, email.value, password.value, confirmPassword.value)
}
</script>

<template>
  <div class="min-h-screen bg-gray-950 flex">

    <!-- Left: Visual panel -->
    <div class="hidden lg:flex flex-1 items-center justify-center bg-gradient-to-br from-gray-900 via-gray-950 to-gray-900 border-r border-gray-800/60 relative overflow-hidden">
      <div class="absolute inset-0">
        <div class="absolute top-1/3 right-1/4 w-80 h-80 bg-primary-500/10 rounded-full blur-3xl" />
        <div class="absolute bottom-1/3 left-1/4 w-64 h-64 bg-primary-700/8 rounded-full blur-3xl" />
      </div>
      <div class="relative z-10 max-w-sm text-center px-8">
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary-500/20 to-primary-700/20 border border-primary-500/20 flex items-center justify-center mx-auto mb-8">
          <UIcon name="i-lucide-rocket" class="size-10 text-primary-400" />
        </div>
        <h2 class="text-2xl font-bold text-white mb-3">Rejoignez Rentvly</h2>
        <p class="text-gray-500 text-sm leading-relaxed">
          Créez votre compte gratuitement et accédez à toutes nos fonctionnalités d'estimation et d'analyse de rentabilité.
        </p>
        <div class="mt-10 space-y-3 text-left">
          <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-800/40 border border-gray-700/40">
            <div class="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
              <UIcon name="i-lucide-calculator" class="size-4 text-primary-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-200">Estimations illimitées</p>
              <p class="text-xs text-gray-500">Estimez autant de biens que vous voulez</p>
            </div>
          </div>
          <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-800/40 border border-gray-700/40">
            <div class="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
              <UIcon name="i-lucide-trending-up" class="size-4 text-primary-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-200">Analyse de rentabilité</p>
              <p class="text-xs text-gray-500">Rendement brut, net et cashflow</p>
            </div>
          </div>
          <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-800/40 border border-gray-700/40">
            <div class="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
              <UIcon name="i-lucide-history" class="size-4 text-primary-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-200">Historique sauvegardé</p>
              <p class="text-xs text-gray-500">Retrouvez vos analyses à tout moment</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="flex-1 flex items-center justify-center px-4 sm:px-8">
      <div class="w-full max-w-md">
        <!-- Logo -->
        <NuxtLink to="/" class="flex items-center gap-2.5 mb-10">
          <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/20">
            <UIcon name="i-lucide-building-2" class="size-5 text-white" />
          </div>
          <span class="text-xl font-bold text-white tracking-tight">
            Rent<span class="text-primary-400">vly</span>
          </span>
        </NuxtLink>

        <div class="mb-8">
          <h1 class="text-2xl font-bold text-white">Créer votre compte</h1>
          <p class="mt-2 text-sm text-gray-500">Inscrivez-vous gratuitement en quelques secondes.</p>
        </div>

        <!-- Error -->
        <div v-if="error" class="mb-6 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400">
          {{ error }}
        </div>

        <!-- Social -->
        <div class="grid grid-cols-2 gap-3 mb-6">
          <button class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-gray-800 bg-gray-900 text-sm text-gray-300 hover:bg-gray-800 hover:border-gray-700 transition-colors">
            <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Google
          </button>
          <button class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-gray-800 bg-gray-900 text-sm text-gray-300 hover:bg-gray-800 hover:border-gray-700 transition-colors">
            <UIcon name="i-lucide-github" class="size-4" />
            GitHub
          </button>
        </div>

        <div class="flex items-center gap-3 mb-6">
          <div class="flex-1 h-px bg-gray-800" />
          <span class="text-xs text-gray-600 uppercase tracking-wider">ou par email</span>
          <div class="flex-1 h-px bg-gray-800" />
        </div>

        <form @submit.prevent="handleRegister" class="space-y-5">
          <div class="grid grid-cols-2 gap-3">
            <UFormField label="Prénom">
              <UInput v-model="firstName" placeholder="Jean" icon="i-lucide-user" size="lg" required />
            </UFormField>
            <UFormField label="Nom">
              <UInput v-model="lastName" placeholder="Dupont" size="lg" required />
            </UFormField>
          </div>

          <UFormField label="Email">
            <UInput v-model="email" type="email" placeholder="vous@exemple.com" icon="i-lucide-mail" size="lg" required />
          </UFormField>

          <UFormField label="Mot de passe">
            <UInput
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Minimum 8 caractères"
              icon="i-lucide-lock"
              size="lg"
              required
            >
              <template #trailing>
                <button type="button" class="text-gray-500 hover:text-gray-300" @click="showPassword = !showPassword">
                  <UIcon :name="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="size-4" />
                </button>
              </template>
            </UInput>
            <div v-if="password && passwordStrength?.label" class="flex items-center gap-2 mt-2">
              <div class="flex-1 flex gap-1">
                <div v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full transition-colors" :class="i <= (passwordStrength?.score ?? 0) ? passwordStrength?.color : 'bg-gray-800'" />
              </div>
              <span class="text-xs text-gray-500">{{ passwordStrength?.label }}</span>
            </div>
          </UFormField>

          <UFormField label="Confirmer le mot de passe">
            <UInput
              v-model="confirmPassword"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Retapez votre mot de passe"
              icon="i-lucide-lock"
              size="lg"
              required
              :color="passwordsMatch === false ? 'error' : undefined"
            />
            <p v-if="passwordsMatch === false" class="text-xs text-red-400 mt-1">
              Les mots de passe ne correspondent pas
            </p>
          </UFormField>

          <UCheckbox v-model="acceptTerms" required>
            <template #label>
              <span class="text-sm text-gray-400">
                J'accepte les
                <NuxtLink to="/terms" class="text-primary-400 hover:text-primary-300">conditions d'utilisation</NuxtLink>
                et la
                <NuxtLink to="/privacy" class="text-primary-400 hover:text-primary-300">politique de confidentialité</NuxtLink>
              </span>
            </template>
          </UCheckbox>

          <UButton
            type="submit"
            block
            size="lg"
            color="primary"
            :loading="loading"
            :disabled="!acceptTerms || passwordsMatch === false"
            class="shadow-lg shadow-primary-500/20"
          >
            Créer mon compte
          </UButton>
        </form>

        <p class="mt-8 text-center text-sm text-gray-500">
          Déjà un compte ?
          <NuxtLink to="/login" class="text-primary-400 hover:text-primary-300 font-medium transition-colors">
            Se connecter
          </NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>