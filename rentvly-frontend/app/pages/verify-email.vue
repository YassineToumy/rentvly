<script setup lang="ts">
definePageMeta({ layout: false })

useHead({ title: 'Vérification — Rentvly' })

const route = useRoute()
const { verifyEmail, resendVerificationCode, loading, error } = useAuth()

const email = ref<string>(typeof route.query.email === 'string' ? route.query.email : '')
const is2fa = computed(() => route.query.reason === '2fa')
const code = ref('')
const resendOk = ref(false)
const resendCooldown = ref(0)
let timer: ReturnType<typeof setInterval> | null = null

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})

async function handleVerify() {
  if (code.value.length !== 6) return
  await verifyEmail(email.value, code.value)
}

async function handleResend() {
  if (resendCooldown.value > 0) return
  resendOk.value = false
  const ok = await resendVerificationCode(email.value)
  if (ok) {
    resendOk.value = true
    resendCooldown.value = 30
    timer = setInterval(() => {
      resendCooldown.value--
      if (resendCooldown.value <= 0 && timer) clearInterval(timer)
    }, 1000)
  }
}
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-slate-950 flex items-center justify-center px-6 py-10">
    <div class="w-full max-w-[420px]">
      <NuxtLink to="/" class="mb-12 flex justify-center">
        <img src="/logo.png" alt="Rentvly" class="h-16 sm:h-18 w-auto object-contain">
      </NuxtLink>

      <div class="mb-8 text-center">
        <div class="w-16 h-16 rounded-2xl bg-primary-500/10 border border-primary-500/20 flex items-center justify-center mx-auto mb-5">
          <UIcon name="i-lucide-mail-check" class="size-8 text-emerald-800 dark:text-primary-400" />
        </div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
          {{ is2fa ? 'Vérification en deux étapes' : 'Vérifiez votre e-mail' }}
        </h1>
        <p class="mt-2 text-sm text-slate-500">
          {{ is2fa
            ? 'Pour sécuriser votre connexion, saisissez le code à 6 chiffres envoyé à'
            : 'Nous avons envoyé un code à 6 chiffres à' }}
          <span class="font-medium text-slate-700 dark:text-slate-300">{{ email || 'votre adresse' }}</span>.
        </p>
      </div>

      <div
        v-if="error"
        class="mb-6 w-full p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-500"
      >
        {{ error }}
      </div>
      <div
        v-else-if="resendOk"
        class="mb-6 w-full p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-sm text-emerald-700 dark:text-emerald-400"
      >
        Un nouveau code vient d’être envoyé.
      </div>

      <form class="w-full space-y-5" @submit.prevent="handleVerify">
        <UFormField label="Adresse e-mail" class="w-full">
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

        <UFormField label="Code de vérification" class="w-full">
          <UInput
            v-model="code"
            placeholder="123456"
            icon="i-lucide-key-round"
            size="lg"
            maxlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            required
            class="w-full tracking-[0.5em] font-semibold"
          />
        </UFormField>

        <UButton
          type="submit"
          block
          size="lg"
          color="primary"
          :loading="loading"
          :disabled="code.length !== 6 || !email"
          class="w-full h-11 shadow-lg shadow-primary-500/20"
        >
          {{ is2fa ? 'Se connecter' : 'Vérifier mon compte' }}
        </UButton>
      </form>

      <div class="mt-6 text-center text-sm text-slate-500">
        Vous n’avez pas reçu le code ?
        <button
          type="button"
          class="text-emerald-800 dark:text-primary-400 font-medium hover:underline disabled:opacity-50"
          :disabled="resendCooldown > 0"
          @click="handleResend"
        >
          Renvoyer{{ resendCooldown > 0 ? ` (${resendCooldown}s)` : '' }}
        </button>
      </div>

      <p class="mt-8 text-center text-sm text-slate-500">
        <NuxtLink to="/login" class="text-emerald-800 dark:text-primary-400 font-medium">
          Retour à la connexion
        </NuxtLink>
      </p>
    </div>
  </div>
</template>
