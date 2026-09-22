<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const colorMode = useColorMode()
const toast = useToast()
const { user, fetchUser, updateEmail, updatePassword, apiErrorMessage } = useAuth()

const themeOptions = [
  { value: 'light', label: 'Clair', icon: 'i-lucide-sun', description: 'Fond blanc, interface lumineuse' },
  { value: 'dark', label: 'Sombre', icon: 'i-lucide-moon', description: 'Thème actuel de Rentvly' },
] as const

function setTheme(value: 'light' | 'dark') {
  colorMode.preference = value
}

onMounted(() => {
  fetchUser()
})

const emailForm = reactive({
  email: '',
  currentPassword: '',
})
const emailError = ref<string | null>(null)
const emailSaving = ref(false)
const showEmailPassword = ref(false)

watch(user, (u) => {
  if (u) emailForm.email = u.email
}, { immediate: true })

const passwordForm = reactive({
  currentPassword: '',
  password: '',
  confirmation: '',
})
const passwordError = ref<string | null>(null)
const passwordSaving = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)

async function handleEmailUpdate() {
  emailError.value = null
  emailSaving.value = true
  try {
    const res = await updateEmail(emailForm.email.trim(), emailForm.currentPassword)
    if (res.success) {
      emailForm.currentPassword = ''
      toast.add({ title: res.message || 'Email mis à jour.', color: 'success' })
    }
  } catch (e: any) {
    emailError.value = apiErrorMessage(e, "Impossible de mettre à jour l'email.")
  } finally {
    emailSaving.value = false
  }
}

async function handlePasswordUpdate() {
  passwordError.value = null
  if (passwordForm.password !== passwordForm.confirmation) {
    passwordError.value = 'Les mots de passe ne correspondent pas.'
    return
  }
  passwordSaving.value = true
  try {
    const res = await updatePassword(
      passwordForm.currentPassword,
      passwordForm.password,
      passwordForm.confirmation,
    )
    if (res.success) {
      passwordForm.currentPassword = ''
      passwordForm.password = ''
      passwordForm.confirmation = ''
      toast.add({ title: res.message || 'Mot de passe mis à jour.', color: 'success' })
    }
  } catch (e: any) {
    passwordError.value = apiErrorMessage(e, 'Impossible de mettre à jour le mot de passe.')
  } finally {
    passwordSaving.value = false
  }
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

      <section class="app-card p-6">
        <div class="flex items-start gap-3 mb-6">
          <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
            <UIcon name="i-lucide-mail" class="size-5 text-primary-500" />
          </div>
          <div>
            <h2 class="text-lg font-semibold app-heading">
              Email
            </h2>
            <p class="text-sm app-muted mt-0.5">
              Modifiez l'adresse associée à votre compte.
            </p>
          </div>
        </div>

        <form class="space-y-4" @submit.prevent="handleEmailUpdate">
          <div
            v-if="emailError"
            class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-500"
          >
            {{ emailError }}
          </div>

          <UFormField label="Nouvel email" class="w-full">
            <UInput
              v-model="emailForm.email"
              type="email"
              placeholder="vous@exemple.com"
              icon="i-lucide-mail"
              size="lg"
              required
              class="w-full"
            />
          </UFormField>

          <UFormField label="Mot de passe actuel" class="w-full">
            <UInput
              v-model="emailForm.currentPassword"
              :type="showEmailPassword ? 'text' : 'password'"
              placeholder="Confirmez avec votre mot de passe"
              icon="i-lucide-lock"
              size="lg"
              required
              class="w-full"
            >
              <template #trailing>
                <button
                  type="button"
                  class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                  @click="showEmailPassword = !showEmailPassword"
                >
                  <UIcon :name="showEmailPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="size-4" />
                </button>
              </template>
            </UInput>
          </UFormField>

          <UButton
            type="submit"
            color="primary"
            size="lg"
            :loading="emailSaving"
            :disabled="!emailForm.email || !emailForm.currentPassword"
          >
            Enregistrer l'email
          </UButton>
        </form>
      </section>

      <section class="app-card p-6">
        <div class="flex items-start gap-3 mb-6">
          <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
            <UIcon name="i-lucide-key-round" class="size-5 text-primary-500" />
          </div>
          <div>
            <h2 class="text-lg font-semibold app-heading">
              Mot de passe
            </h2>
            <p class="text-sm app-muted mt-0.5">
              Choisissez un nouveau mot de passe d'au moins 8 caractères.
            </p>
          </div>
        </div>

        <form class="space-y-4" @submit.prevent="handlePasswordUpdate">
          <div
            v-if="passwordError"
            class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-500"
          >
            {{ passwordError }}
          </div>

          <UFormField label="Mot de passe actuel" class="w-full">
            <UInput
              v-model="passwordForm.currentPassword"
              :type="showCurrentPassword ? 'text' : 'password'"
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
                  @click="showCurrentPassword = !showCurrentPassword"
                >
                  <UIcon :name="showCurrentPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="size-4" />
                </button>
              </template>
            </UInput>
          </UFormField>

          <UFormField label="Nouveau mot de passe" class="w-full">
            <UInput
              v-model="passwordForm.password"
              :type="showNewPassword ? 'text' : 'password'"
              placeholder="Minimum 8 caractères"
              icon="i-lucide-lock"
              size="lg"
              required
              minlength="8"
              class="w-full"
            >
              <template #trailing>
                <button
                  type="button"
                  class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                  @click="showNewPassword = !showNewPassword"
                >
                  <UIcon :name="showNewPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="size-4" />
                </button>
              </template>
            </UInput>
          </UFormField>

          <UFormField label="Confirmer le mot de passe" class="w-full">
            <UInput
              v-model="passwordForm.confirmation"
              :type="showNewPassword ? 'text' : 'password'"
              placeholder="Retapez le nouveau mot de passe"
              icon="i-lucide-lock"
              size="lg"
              required
              class="w-full"
            />
          </UFormField>

          <UButton
            type="submit"
            color="primary"
            size="lg"
            :loading="passwordSaving"
            :disabled="!passwordForm.currentPassword || !passwordForm.password || !passwordForm.confirmation"
          >
            Enregistrer le mot de passe
          </UButton>
        </form>
      </section>
    </div>
  </div>
</template>
