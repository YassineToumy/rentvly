<script setup lang="ts">
definePageMeta({ layout: false })

useHead({
  title: 'Connexion — Rentvly',
})

const { login, loading, error, isAuthenticated, user, fetchUser } = useAuth()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const rememberMe = ref(false)

onMounted(async () => {
  if (isAuthenticated.value && !user.value) await fetchUser()
  if (isAuthenticated.value) {
    navigateTo(user.value?.role === 'admin' ? '/admin' : '/dashboard')
  }
})

async function handleLogin() {
  await login(email.value, password.value)
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-slate-950 flex">
    <!-- Form column -->
    <div class="w-full lg:w-1/2 flex items-center justify-center px-6 sm:px-10 py-10">
      <div class="w-full max-w-[420px]">
        <NuxtLink to="/" class="mb-14 flex justify-center">
          <img
            src="/logo.png"
            alt="Rentvly"
            class="h-18 sm:h-20 w-auto object-contain"
          >
        </NuxtLink>

        <div class="mb-8">
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Content de vous revoir</h1>
          <p class="mt-2 text-sm text-slate-500">
            Connectez-vous pour accéder à votre espace investisseur ou admin.
          </p>
        </div>

        <div
          v-if="error"
          class="mb-6 w-full p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-500"
        >
          {{ error }}
        </div>

        <form class="w-full space-y-5" @submit.prevent="handleLogin">
          <UFormField label="Email" class="w-full">
            <UInput
              v-model="email"
              type="email"
              placeholder="vous@exemple.com"
              icon="i-lucide-mail"
              size="lg"
              required
              class="w-full"
            />
          </UFormField>

          <UFormField label="Mot de passe" class="w-full">
            <UInput
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              icon="i-lucide-lock"
              size="lg"
              required
              class="w-full"
            >
              <template #trailing>
                <button
                  type="button"
                  class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                  @click="showPassword = !showPassword"
                >
                  <UIcon :name="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="size-4" />
                </button>
              </template>
            </UInput>
          </UFormField>

          <div class="flex items-center justify-between gap-3 w-full">
            <UCheckbox v-model="rememberMe" label="Se souvenir de moi" />
            <NuxtLink to="/forgot-password" class="text-xs text-primary-500 hover:text-primary-400 shrink-0">
              Mot de passe oublié ?
            </NuxtLink>
          </div>

          <UButton
            type="submit"
            block
            size="lg"
            color="primary"
            :loading="loading"
            class="w-full h-11 shadow-lg shadow-primary-500/20"
          >
            Se connecter
          </UButton>
        </form>

        <p class="mt-8 text-center text-sm text-slate-500">
          Pas encore de compte ?
          <NuxtLink to="/register" class="text-primary-500 hover:text-primary-400 font-medium">
            Créer un compte
          </NuxtLink>
        </p>
      </div>
    </div>

    <!-- Visual column -->
    <div class="hidden lg:flex w-1/2 items-center justify-center bg-slate-900 border-l border-slate-800 relative overflow-hidden">
      <div class="absolute inset-0">
        <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-primary-500/15 rounded-full blur-3xl" />
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-primary-700/10 rounded-full blur-3xl" />
      </div>
      <div class="relative z-10 w-full max-w-[420px] text-center px-10">
        <div class="w-20 h-20 rounded-2xl bg-primary-500/15 border border-primary-500/25 flex items-center justify-center mx-auto mb-8">
          <UIcon name="i-lucide-bar-chart-3" class="size-10 text-primary-400" />
        </div>
        <h2 class="text-2xl font-bold text-white mb-3">Estimez & Investissez</h2>
        <p class="text-slate-400 text-sm leading-relaxed">
          Accédez à des estimations de loyers basées sur l'IA et calculez la rentabilité de vos investissements.
        </p>
        <div class="mt-10 grid grid-cols-2 gap-3">
          <div class="h-20 p-4 rounded-xl bg-white/5 border border-white/10 text-left flex flex-col justify-center">
            <p class="text-xs text-slate-400">Précision</p>
            <p class="text-lg font-bold text-primary-400">plus que 87%</p>
          </div>
          <div class="h-20 p-4 rounded-xl bg-white/5 border border-white/10 text-left flex flex-col justify-center">
            <p class="text-xs text-slate-400">Annonces</p>
            <p class="text-lg font-bold text-white">50k+</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
