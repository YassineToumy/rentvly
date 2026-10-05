<script setup lang="ts">
const { authGateOpen, authGateTitle, authGateMessage, closeAuthGate } = useAuthGate()

const loginRedirect = computed(() => {
  const route = useRoute()
  return { path: '/login', query: { redirect: route.fullPath } }
})

function goLogin() {
  closeAuthGate()
  navigateTo(loginRedirect.value)
}

function goRegister() {
  closeAuthGate()
  const route = useRoute()
  navigateTo({ path: '/register', query: { redirect: route.fullPath } })
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="authGateOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="authGateTitle"
      >
        <div
          class="absolute inset-0 bg-gray-950/50 dark:bg-black/70 backdrop-blur-md"
          @click="closeAuthGate"
        />

        <Transition
          appear
          enter-active-class="transition duration-250 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-2"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="authGateOpen"
            class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gray-300/80 dark:border-gray-800 bg-[#f4f5f7] dark:bg-gray-900 shadow-2xl shadow-black/20"
          >
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary-400 via-primary-500 to-emerald-400" />

            <button
              type="button"
              class="absolute top-3 right-3 p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              aria-label="Fermer"
              @click="closeAuthGate"
            >
              <UIcon name="i-lucide-x" class="size-4" />
            </button>

            <div class="px-6 pt-8 pb-6 text-center">
              <div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-400/20 to-primary-600/30 ring-1 ring-primary-500/30">
                <UIcon name="i-lucide-lock-keyhole" class="size-7 text-emerald-800 dark:text-primary-400" />
              </div>

              <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ authGateTitle }}
              </h2>
              <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                {{ authGateMessage }}
              </p>

              <div class="mt-6 flex flex-col gap-2.5">
                <UButton
                  block
                  color="primary"
                  size="lg"
                  icon="i-lucide-log-in"
                  class="shadow-lg shadow-primary-500/25"
                  @click="goLogin"
                >
                  Se connecter
                </UButton>
                <UButton
                  block
                  variant="outline"
                  color="neutral"
                  size="lg"
                  icon="i-lucide-user-plus"
                  @click="goRegister"
                >
                  Créer un compte
                </UButton>
                <button
                  type="button"
                  class="mt-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                  @click="closeAuthGate"
                >
                  Continuer en tant que visiteur
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
