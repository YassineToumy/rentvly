const authGateOpen = ref(false)
const authGateMessage = ref(
  'Connectez-vous pour accéder à cette fonctionnalité.'
)
const authGateTitle = ref('Connexion requise')

const REASONS = {
  estimate: 'Pour estimer un bien ou calculer sa rentabilité, connectez-vous à votre compte.',
  predict: 'Votre formulaire est sauvegardé. Connectez-vous pour lancer l’estimation.',
  stats: 'Pour consulter les statistiques du marché, connectez-vous à votre compte.',
  default: 'Connectez-vous pour accéder à cette fonctionnalité.',
} as const

export type AuthGateReason = keyof typeof REASONS

export function useAuthGate() {
  const { isAuthenticated } = useAuth()

  function openAuthGate(reason: AuthGateReason = 'default', title = 'Connexion requise') {
    authGateTitle.value = title
    authGateMessage.value = REASONS[reason] ?? REASONS.default
    authGateOpen.value = true
  }

  function closeAuthGate() {
    authGateOpen.value = false
  }

  /** Returns true if authenticated; otherwise opens the modal and returns false. */
  function requireAuth(reason: AuthGateReason = 'default'): boolean {
    if (isAuthenticated.value) return true
    openAuthGate(reason)
    return false
  }

  return {
    authGateOpen,
    authGateTitle,
    authGateMessage,
    openAuthGate,
    closeAuthGate,
    requireAuth,
  }
}
