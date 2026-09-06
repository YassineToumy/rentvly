<script setup lang="ts">
definePageMeta({ layout: false })

useHead({
  title: 'Inscription — Rentvly',
})

const { register, loading, error, isAuthenticated, user, fetchUser } = useAuth()

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const acceptTerms = ref(false)

onMounted(async () => {
  if (isAuthenticated.value && !user.value) await fetchUser()
  if (isAuthenticated.value) {
    navigateTo(user.value?.role === 'admin' ? '/admin' : '/dashboard')
  }
})

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
  <div class="min-h-screen bg-white dark:bg-slate-950 flex">
    <!-- Visual column -->
    <div class="hidden lg:flex w-1/2 items-center justify-center bg-slate-900 border-r border-slate-800 relative overflow-hidden">
      <div class="absolute inset-0">
        <div class="absolute top-1/3 right-1/4 w-80 h-80 bg-primary-500/15 rounded-full blur-3xl" />
        <div class="absolute bottom-1/3 left-1/4 w-64 h-64 bg-primary-700/10 rounded-full blur-3xl" />
      </div>
      <div class="relative z-10 w-full max-w-[420px] text-center px-10">
        <div class="w-20 h-20 rounded-2xl bg-primary-500/15 border border-primary-500/25 flex items-center justify-center mx-auto mb-8">
          <UIcon name="i-lucide-rocket" class="size-10 text-primary-400" />
        </div>
        <h2 class="text-2xl font-bold text-white mb-3">Rejoignez Rentvly</h2>
        <p class="text-slate-400 text-sm leading-relaxed mb-10">
          Créez votre compte et accédez aux estimations et analyses de rentabilité.
        </p>
        <div class="space-y-3 text-left">
          <div
            v-for="item in [
              { icon: 'i-lucide-calculator', title: 'Estimations illimitées', desc: 'Estimez autant de biens que vous voulez' },
              { icon: 'i-lucide-trending-up', title: 'Analyse de rentabilité', desc: 'Rendement brut, net et cashflow' },
              { icon: 'i-lucide-history', title: 'Historique sauvegardé', desc: 'Retrouvez vos analyses à tout moment' },
            ]"
            :key="item.title"
            class="flex items-center gap-3 h-[68px] px-4 rounded-xl bg-white/5 border border-white/10"
          >
            <div class="w-9 h-9 rounded-lg bg-primary-500/15 flex items-center justify-center shrink-0">
              <UIcon :name="item.icon" class="size-4 text-primary-400" />
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-100">{{ item.title }}</p>
              <p class="text-xs text-slate-400">{{ item.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form column -->
    <div class="w-full lg:w-1/2 flex items-center justify-center px-6 sm:px-10 py-10">
      <div class="w-full max-w-[420px]">
        <NuxtLink to="/" class="inline-flex items-center mb-10">
          <img
            src="/logo.png"
            alt="Rentvly"
            class="h-10 w-auto object-contain"
          >
        </NuxtLink>

        <div class="mb-8">
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Créer votre compte</h1>
          <p class="mt-2 text-sm text-slate-500">Inscrivez-vous gratuitement en quelques secondes.</p>
        </div>

        <div
          v-if="error"
          class="mb-6 w-full p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-500"
        >
          {{ error }}
        </div>

        <form class="w-full space-y-5" @submit.prevent="handleRegister">
          <div class="grid grid-cols-2 gap-3 w-full">
            <UFormField label="Prénom" class="w-full min-w-0">
              <UInput
                v-model="firstName"
                placeholder="Jean"
                icon="i-lucide-user"
                size="lg"
                required
                class="w-full"
              />
            </UFormField>
            <UFormField label="Nom" class="w-full min-w-0">
              <UInput
                v-model="lastName"
                placeholder="Dupont"
                icon="i-lucide-user"
                size="lg"
                required
                class="w-full"
              />
            </UFormField>
          </div>

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
              placeholder="Minimum 8 caractères"
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
            <div v-if="password && passwordStrength?.label" class="flex items-center gap-2 mt-2 w-full">
              <div class="flex-1 flex gap-1">
                <div
                  v-for="i in 4"
                  :key="i"
                  class="h-1 flex-1 rounded-full transition-colors"
                  :class="i <= (passwordStrength?.score ?? 0) ? passwordStrength?.color : 'bg-slate-200 dark:bg-slate-800'"
                />
              </div>
              <span class="text-xs text-slate-500 shrink-0">{{ passwordStrength?.label }}</span>
            </div>
          </UFormField>

          <UFormField label="Confirmer le mot de passe" class="w-full">
            <UInput
              v-model="confirmPassword"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Retapez votre mot de passe"
              icon="i-lucide-lock"
              size="lg"
              required
              class="w-full"
              :color="passwordsMatch === false ? 'error' : undefined"
            />
            <p v-if="passwordsMatch === false" class="text-xs text-red-500 mt-1">
              Les mots de passe ne correspondent pas
            </p>
          </UFormField>

          <UCheckbox v-model="acceptTerms" required class="w-full">
            <template #label>
              <span class="text-sm text-slate-500">
                J'accepte les
                <NuxtLink to="/terms" class="text-primary-500 hover:text-primary-400">conditions d'utilisation</NuxtLink>
                et la
                <NuxtLink to="/privacy" class="text-primary-500 hover:text-primary-400">politique de confidentialité</NuxtLink>
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
            class="w-full h-11 shadow-lg shadow-primary-500/20"
          >
            Créer mon compte
          </UButton>
        </form>

        <p class="mt-8 text-center text-sm text-slate-500">
          Déjà un compte ?
          <NuxtLink to="/login" class="text-primary-500 hover:text-primary-400 font-medium">
            Se connecter
          </NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>
