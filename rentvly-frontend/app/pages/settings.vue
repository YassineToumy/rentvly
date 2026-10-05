<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

useHead({ title: 'Paramètres — Rentvly' })

const colorMode = useColorMode()
const toast = useToast()
const apiBase = useApiBase()
const { user, authHeaders, fetchUser } = useAuth()

const themeOptions = [
  { value: 'light', label: 'Clair', icon: 'i-lucide-sun', description: 'Fond doux, cartes avec ombre' },
  { value: 'dark', label: 'Sombre', icon: 'i-lucide-moon', description: 'Thème actuel de Rentvly' },
] as const

function setTheme(value: 'light' | 'dark') {
  colorMode.preference = value
}

onMounted(() => {
  if (!user.value) fetchUser()
  else {
    name.value = user.value.name || ''
    email.value = user.value.email || ''
  }
})

watch(user, (u) => {
  if (!u) return
  name.value = u.name || ''
  email.value = u.email || ''
})

// ── Profile (name / email) ──
const name = ref('')
const email = ref('')
const profileLoading = ref(false)
const profileError = ref<string | null>(null)

async function saveProfile() {
  profileError.value = null
  profileLoading.value = true
  try {
    const res = await $fetch<{ success: boolean; message?: string; data?: any; error?: string }>(
      `${apiBase}/me`,
      {
        method: 'PATCH',
        headers: authHeaders(),
        body: { name: name.value.trim(), email: email.value.trim() },
      },
    )
    if (!res.success) {
      profileError.value = res.error || 'Impossible de mettre à jour le profil.'
      return
    }
    if (res.data) user.value = { ...user.value!, ...res.data }
    toast.add({ title: res.message || 'Profil mis à jour', color: 'success' })
  } catch (e: any) {
    profileError.value = e?.data?.message || e?.data?.error || e?.data?.errors?.email?.[0] || 'Erreur lors de la mise à jour.'
  } finally {
    profileLoading.value = false
  }
}

// ── Password ──
const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')
const showPasswords = ref(false)
const passwordLoading = ref(false)
const passwordError = ref<string | null>(null)

async function savePassword() {
  passwordError.value = null
  if (newPassword.value !== newPasswordConfirmation.value) {
    passwordError.value = 'Les mots de passe ne correspondent pas.'
    return
  }
  passwordLoading.value = true
  try {
    const res = await $fetch<{ success: boolean; message?: string; error?: string }>(
      `${apiBase}/me/password`,
      {
        method: 'PATCH',
        headers: authHeaders(),
        body: {
          current_password: currentPassword.value,
          password: newPassword.value,
          password_confirmation: newPasswordConfirmation.value,
        },
      },
    )
    if (!res.success) {
      passwordError.value = res.error || 'Impossible de changer le mot de passe.'
      return
    }
    currentPassword.value = ''
    newPassword.value = ''
    newPasswordConfirmation.value = ''
    toast.add({ title: res.message || 'Mot de passe mis à jour', color: 'success' })
  } catch (e: any) {
    passwordError.value = e?.data?.error || e?.data?.message || 'Erreur lors du changement de mot de passe.'
  } finally {
    passwordLoading.value = false
  }
}

// ── Avatar ──
const avatarInput = ref<HTMLInputElement | null>(null)
const avatarLoading = ref(false)
const avatarPreview = ref<string | null>(null)

const displayAvatar = computed(() => avatarPreview.value || user.value?.avatar_url || null)
const userInitial = computed(() => (user.value?.name || 'U').charAt(0).toUpperCase())

function pickAvatar() {
  avatarInput.value?.click()
}

async function onAvatarSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    toast.add({ title: 'Fichier invalide', description: 'Choisissez une image JPEG, PNG ou WebP.', color: 'error' })
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    toast.add({ title: 'Image trop lourde', description: 'Maximum 2 Mo.', color: 'error' })
    return
  }

  avatarPreview.value = URL.createObjectURL(file)
  avatarLoading.value = true
  try {
    const form = new FormData()
    form.append('avatar', file)
    const res = await $fetch<{ success: boolean; message?: string; data?: any; error?: string }>(
      `${apiBase}/me/avatar`,
      {
        method: 'POST',
        headers: authHeaders(),
        body: form,
      },
    )
    if (!res.success) {
      toast.add({ title: res.error || 'Échec de l’upload', color: 'error' })
      avatarPreview.value = null
      return
    }
    if (res.data) user.value = { ...user.value!, ...normalizeAvatar(res.data) }
    toast.add({ title: res.message || 'Photo mise à jour', color: 'success' })
  } catch (e: any) {
    avatarPreview.value = null
    toast.add({
      title: e?.data?.message || e?.data?.error || 'Impossible d’envoyer la photo',
      color: 'error',
    })
  } finally {
    avatarLoading.value = false
    input.value = ''
  }
}

function normalizeAvatar(data: any) {
  if (data?.avatar_url) return data
  if (data?.avatar) {
    const origin = apiBase.replace(/\/api\/v1\/?$/, '')
    return { ...data, avatar_url: `${origin}/${String(data.avatar).replace(/^\//, '')}` }
  }
  return data
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
          Gérez votre profil, votre sécurité et l’apparence de Rentvly.
        </p>
      </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
      <!-- Photo -->
      <section class="app-card p-6">
        <div class="flex items-start gap-3 mb-6">
          <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
            <UIcon name="i-lucide-camera" class="size-5 text-emerald-800 dark:text-primary-400" />
          </div>
          <div>
            <h2 class="text-lg font-semibold app-heading">
              Photo de profil
            </h2>
            <p class="text-sm app-muted mt-0.5">
              JPEG, PNG ou WebP — 2 Mo max.
            </p>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-5">
          <div class="relative">
            <div class="size-24 rounded-2xl overflow-hidden ring-2 ring-primary-500/30 bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
              <img
                v-if="displayAvatar"
                :src="displayAvatar"
                alt="Avatar"
                class="size-full object-cover"
              >
              <span v-else class="text-3xl font-bold text-white">{{ userInitial }}</span>
            </div>
            <div
              v-if="avatarLoading"
              class="absolute inset-0 rounded-2xl bg-black/40 flex items-center justify-center"
            >
              <UIcon name="i-lucide-loader-2" class="size-6 text-white animate-spin" />
            </div>
          </div>
          <div class="flex flex-col gap-2 w-full sm:w-auto">
            <input
              ref="avatarInput"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              class="hidden"
              @change="onAvatarSelected"
            >
            <UButton
              color="primary"
              icon="i-lucide-upload"
              :loading="avatarLoading"
              class="shadow-lg shadow-primary-500/20"
              @click="pickAvatar"
            >
              Choisir une photo
            </UButton>
            <p class="text-xs app-muted text-center sm:text-left">
              Visible dans la barre de navigation.
            </p>
          </div>
        </div>
      </section>

      <!-- Nom / Email -->
      <section class="app-card p-6">
        <div class="flex items-start gap-3 mb-6">
          <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
            <UIcon name="i-lucide-user" class="size-5 text-emerald-800 dark:text-primary-400" />
          </div>
          <div>
            <h2 class="text-lg font-semibold app-heading">
              Informations personnelles
            </h2>
            <p class="text-sm app-muted mt-0.5">
              Modifiez votre nom et votre adresse e-mail.
            </p>
          </div>
        </div>

        <div
          v-if="profileError"
          class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-500"
        >
          {{ profileError }}
        </div>

        <form class="space-y-4" @submit.prevent="saveProfile">
          <UFormField label="Nom complet" class="w-full">
            <UInput v-model="name" size="lg" icon="i-lucide-user" required class="w-full" />
          </UFormField>
          <UFormField label="E-mail" class="w-full">
            <UInput v-model="email" type="email" size="lg" icon="i-lucide-mail" required class="w-full" />
          </UFormField>
          <div class="flex justify-end pt-2">
            <UButton
              type="submit"
              color="primary"
              icon="i-lucide-save"
              :loading="profileLoading"
              class="shadow-lg shadow-primary-500/20"
            >
              Enregistrer
            </UButton>
          </div>
        </form>
      </section>

      <!-- Password -->
      <section class="app-card p-6">
        <div class="flex items-start gap-3 mb-6">
          <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
            <UIcon name="i-lucide-lock-keyhole" class="size-5 text-emerald-800 dark:text-primary-400" />
          </div>
          <div>
            <h2 class="text-lg font-semibold app-heading">
              Mot de passe
            </h2>
            <p class="text-sm app-muted mt-0.5">
              Changez votre mot de passe (minimum 8 caractères).
            </p>
          </div>
        </div>

        <div
          v-if="passwordError"
          class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-500"
        >
          {{ passwordError }}
        </div>

        <form class="space-y-4" @submit.prevent="savePassword">
          <UFormField label="Mot de passe actuel" class="w-full">
            <UInput
              v-model="currentPassword"
              :type="showPasswords ? 'text' : 'password'"
              size="lg"
              icon="i-lucide-lock"
              required
              class="w-full"
            />
          </UFormField>
          <UFormField label="Nouveau mot de passe" class="w-full">
            <UInput
              v-model="newPassword"
              :type="showPasswords ? 'text' : 'password'"
              size="lg"
              icon="i-lucide-key-round"
              required
              class="w-full"
            />
          </UFormField>
          <UFormField label="Confirmer le nouveau mot de passe" class="w-full">
            <UInput
              v-model="newPasswordConfirmation"
              :type="showPasswords ? 'text' : 'password'"
              size="lg"
              icon="i-lucide-key-round"
              required
              class="w-full"
            />
          </UFormField>
          <label class="flex items-center gap-2 text-sm app-muted cursor-pointer">
            <input v-model="showPasswords" type="checkbox" class="rounded border-gray-300">
            Afficher les mots de passe
          </label>
          <div class="flex justify-end pt-2">
            <UButton
              type="submit"
              color="primary"
              icon="i-lucide-shield-check"
              :loading="passwordLoading"
              class="shadow-lg shadow-primary-500/20"
            >
              Mettre à jour le mot de passe
            </UButton>
          </div>
        </form>
      </section>

      <!-- Theme -->
      <section class="app-card p-6">
        <div class="flex items-start gap-3 mb-6">
          <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
            <UIcon name="i-lucide-palette" class="size-5 text-emerald-800 dark:text-primary-400" />
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
              :class="option.value === 'light' ? 'bg-[#f4f5f7] border border-gray-300/80 shadow-sm' : 'bg-gray-900 border border-gray-700'"
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
              class="size-5 text-emerald-800 dark:text-primary-400 ml-auto flex-shrink-0"
            />
          </button>
        </div>
      </section>

    </div>
  </div>
</template>
