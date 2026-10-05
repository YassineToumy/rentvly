<script setup lang="ts">
definePageMeta({ layout: false })

useHead({
  title: 'Mot de passe oublié — Rentvly',
})

const apiBase = useApiBase()

type Step = 'email' | 'code' | 'password' | 'done'

const step = ref<Step>('email')
const email = ref('')
const code = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref<string | null>(null)
const info = ref<string | null>(null)

const codeDigits = computed({
  get: () => code.value,
  set: (v: string) => {
    code.value = String(v || '').replace(/\D/g, '').slice(0, 6)
  },
})

const passwordsMatch = computed(() => {
  if (!passwordConfirmation.value) return null
  return password.value === passwordConfirmation.value
})

async function requestCode() {
  error.value = null
  info.value = null
  loading.value = true
  try {
    const res = await $fetch<{ success: boolean; message?: string; error?: string }>(
      `${apiBase}/forgot-password`,
      {
        method: 'POST',
        body: { email: email.value.trim() },
      },
    )
    if (!res.success) {
      error.value = res.error || "Impossible d'envoyer le code."
      return
    }
    info.value = res.message || 'Un code a été envoyé à votre adresse e-mail.'
    step.value = 'code'
  } catch (e: any) {
    error.value = e?.data?.message || e?.data?.error || "Impossible d'envoyer le code."
  } finally {
    loading.value = false
  }
}

async function verifyCode() {
  error.value = null
  if (code.value.length !== 6) {
    error.value = 'Saisissez le code à 6 chiffres reçu par e-mail.'
    return
  }
  loading.value = true
  try {
    const res = await $fetch<{ success: boolean; message?: string; error?: string }>(
      `${apiBase}/verify-reset-code`,
      {
        method: 'POST',
        body: { email: email.value.trim(), code: code.value },
      },
    )
    if (!res.success) {
      error.value = res.error || 'Code invalide ou expiré.'
      return
    }
    info.value = res.message || null
    step.value = 'password'
  } catch (e: any) {
    error.value = e?.data?.error || e?.data?.message || 'Code invalide ou expiré.'
  } finally {
    loading.value = false
  }
}

async function submitNewPassword() {
  error.value = null
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Les mots de passe ne correspondent pas.'
    return
  }
  if (password.value.length < 8) {
    error.value = 'Le mot de passe doit contenir au moins 8 caractères.'
    return
  }
  loading.value = true
  try {
    const res = await $fetch<{ success: boolean; message?: string; error?: string }>(
      `${apiBase}/reset-password`,
      {
        method: 'POST',
        body: {
          email: email.value.trim(),
          code: code.value,
          password: password.value,
          password_confirmation: passwordConfirmation.value,
        },
      },
    )
    if (!res.success) {
      error.value = res.error || 'Impossible de mettre à jour le mot de passe.'
      return
    }
    step.value = 'done'
    info.value = res.message || 'Mot de passe mis à jour.'
  } catch (e: any) {
    error.value = e?.data?.error || e?.data?.message || 'Impossible de mettre à jour le mot de passe.'
  } finally {
    loading.value = false
  }
}

function goBack() {
  error.value = null
  if (step.value === 'code') step.value = 'email'
  else if (step.value === 'password') step.value = 'code'
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-slate-950 flex">
    <div class="w-full lg:w-1/2 flex items-center justify-center px-6 sm:px-10 py-10">
      <div class="w-full max-w-[420px]">
        <NuxtLink to="/" class="mb-14 flex justify-center">
          <img src="/logo.png" alt="Rentvly" class="h-18 sm:h-20 w-auto object-contain">
        </NuxtLink>

        <div class="mb-8">
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
            <template v-if="step === 'email'">Mot de passe oublié</template>
            <template v-else-if="step === 'code'">Vérifiez votre e-mail</template>
            <template v-else-if="step === 'password'">Nouveau mot de passe</template>
            <template v-else>Mot de passe mis à jour</template>
          </h1>
          <p class="mt-2 text-sm text-slate-500">
            <template v-if="step === 'email'">
              Indiquez l’e-mail de votre compte. Nous vous enverrons un code à 6 chiffres.
            </template>
            <template v-else-if="step === 'code'">
              Saisissez le code envoyé à <span class="font-medium text-slate-700 dark:text-slate-300">{{ email }}</span>.
            </template>
            <template v-else-if="step === 'password'">
              Choisissez un nouveau mot de passe sécurisé (min. 8 caractères).
            </template>
            <template v-else>
              Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
            </template>
          </p>
        </div>

        <!-- Progress -->
        <div v-if="step !== 'done'" class="mb-6 flex items-center gap-2">
          <div
            v-for="(s, i) in (['email', 'code', 'password'] as Step[])"
            :key="s"
            class="h-1.5 flex-1 rounded-full transition-colors"
            :class="(['email', 'code', 'password'].indexOf(step) >= i) ? 'bg-primary-500' : 'bg-slate-200 dark:bg-slate-800'"
          />
        </div>

        <div
          v-if="error"
          class="mb-6 w-full p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-500"
        >
          {{ error }}
        </div>
        <div
          v-else-if="info && step !== 'done'"
          class="mb-6 w-full p-3 rounded-lg bg-primary-500/10 border border-primary-500/20 text-sm text-primary-600 dark:text-primary-400"
        >
          {{ info }}
        </div>

        <!-- Step 1: email -->
        <form v-if="step === 'email'" class="space-y-5" @submit.prevent="requestCode">
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
          <UButton
            type="submit"
            block
            size="lg"
            color="primary"
            :loading="loading"
            icon="i-lucide-send"
            class="w-full h-11 shadow-lg shadow-primary-500/20"
          >
            Envoyer le code
          </UButton>
        </form>

        <!-- Step 2: code -->
        <form v-else-if="step === 'code'" class="space-y-5" @submit.prevent="verifyCode">
          <UFormField label="Code à 6 chiffres" class="w-full">
            <UInput
              v-model="codeDigits"
              type="text"
              inputmode="numeric"
              autocomplete="one-time-code"
              placeholder="000000"
              icon="i-lucide-shield-check"
              size="lg"
              required
              maxlength="6"
              class="w-full tracking-[0.35em] font-semibold text-center"
            />
          </UFormField>
          <UButton
            type="submit"
            block
            size="lg"
            color="primary"
            :loading="loading"
            :disabled="code.length !== 6"
            class="w-full h-11 shadow-lg shadow-primary-500/20"
          >
            Vérifier le code
          </UButton>
          <div class="flex items-center justify-between text-sm">
            <button type="button" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="goBack">
              ← Modifier l’e-mail
            </button>
            <button
              type="button"
              class="text-primary-500 hover:text-primary-400 font-medium disabled:opacity-50"
              :disabled="loading"
              @click="requestCode"
            >
              Renvoyer le code
            </button>
          </div>
        </form>

        <!-- Step 3: new password -->
        <form v-else-if="step === 'password'" class="space-y-5" @submit.prevent="submitNewPassword">
          <UFormField label="Nouveau mot de passe" class="w-full">
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
          <UFormField label="Confirmer le mot de passe" class="w-full">
            <UInput
              v-model="passwordConfirmation"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              icon="i-lucide-lock"
              size="lg"
              required
              class="w-full"
            />
            <p
              v-if="passwordsMatch === false"
              class="mt-1.5 text-xs text-red-500"
            >
              Les mots de passe ne correspondent pas.
            </p>
          </UFormField>
          <UButton
            type="submit"
            block
            size="lg"
            color="primary"
            :loading="loading"
            :disabled="passwordsMatch === false"
            icon="i-lucide-check"
            class="w-full h-11 shadow-lg shadow-primary-500/20"
          >
            Enregistrer le mot de passe
          </UButton>
          <button type="button" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300" @click="goBack">
            ← Modifier le code
          </button>
        </form>

        <!-- Done -->
        <div v-else class="space-y-5 text-center">
          <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-primary-500/15 ring-1 ring-primary-500/25">
            <UIcon name="i-lucide-circle-check" class="size-8 text-primary-500" />
          </div>
          <p class="text-sm text-slate-500">{{ info }}</p>
          <UButton
            to="/login"
            block
            size="lg"
            color="primary"
            icon="i-lucide-log-in"
            class="w-full h-11 shadow-lg shadow-primary-500/20"
          >
            Se connecter
          </UButton>
        </div>

        <p v-if="step !== 'done'" class="mt-8 text-center text-sm text-slate-500">
          <NuxtLink to="/login" class="text-primary-500 hover:text-primary-400 font-medium">
            Retour à la connexion
          </NuxtLink>
        </p>
      </div>
    </div>

    <div class="hidden lg:flex w-1/2 items-center justify-center bg-slate-900 border-l border-slate-800 relative overflow-hidden">
      <div class="absolute inset-0">
        <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-primary-500/15 rounded-full blur-3xl" />
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-primary-700/10 rounded-full blur-3xl" />
      </div>
      <div class="relative z-10 w-full max-w-[420px] text-center px-10">
        <div class="w-20 h-20 rounded-2xl bg-primary-500/15 border border-primary-500/25 flex items-center justify-center mx-auto mb-8">
          <UIcon name="i-lucide-key-round" class="size-10 text-primary-400" />
        </div>
        <h2 class="text-2xl font-bold text-white mb-3">Sécurisez votre compte</h2>
        <p class="text-slate-400 text-sm leading-relaxed">
          Un code à usage unique est envoyé à votre adresse e-mail pour valider le changement de mot de passe.
        </p>
      </div>
    </div>
  </div>
</template>
