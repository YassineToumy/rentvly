<script setup lang="ts">
import type { AuthGateReason } from '../composables/useAuthGate'

const props = withDefaults(defineProps<{
  title?: string
  description?: string
  icon?: string
  reason?: AuthGateReason
}>(), {
  reason: 'default',
})

const { openAuthGate } = useAuthGate()

onMounted(() => {
  openAuthGate(props.reason)
})
</script>

<template>
  <div class="min-h-[60vh] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-lg text-center">
      <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-400/15 to-primary-600/25 ring-1 ring-primary-500/25">
        <UIcon :name="icon || 'i-lucide-lock-keyhole'" class="size-8 text-emerald-800 dark:text-primary-400" />
      </div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ title || 'Connexion requise' }}
      </h1>
      <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
        {{ description || 'Cette fonctionnalité est réservée aux utilisateurs connectés.' }}
      </p>
      <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
        <UButton
          to="/login"
          color="primary"
          size="lg"
          icon="i-lucide-log-in"
          class="shadow-lg shadow-primary-500/20"
        >
          Se connecter
        </UButton>
        <UButton to="/listings" variant="outline" color="neutral" size="lg" icon="i-lucide-building">
          Voir les biens
        </UButton>
      </div>
    </div>
  </div>
</template>
