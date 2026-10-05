<script setup lang="ts">
const { requireAuth } = useAuthGate()

function goStats() {
  if (!requireAuth('stats')) return
  navigateTo('/statistiques')
}

const steps = [
  { icon: 'i-lucide-home', title: '1. Décrivez le bien', text: 'Ville, surface, pièces, équipements — les caractéristiques du bien à analyser.' },
  { icon: 'i-lucide-brain', title: '2. Estimation du loyer', text: 'Notre IA prédit le loyer mensuel en comparant avec des milliers d’annonces similaires.' },
  { icon: 'i-lucide-calculator', title: '3. Simulez le financement', text: 'Prix d’achat, apport, crédit, travaux, charges — ajustez tous les paramètres de votre projet.' },
  { icon: 'i-lucide-bar-chart-3', title: '4. Résultats complets', text: 'Rendement brut & net, cashflow mensuel, retour sur investissement et analyse fiscale.' },
]

const features = [
  { icon: 'i-lucide-euro', title: 'Loyer estimé par IA', text: 'Prédiction du loyer mensuel basée sur 50 000+ annonces et un modèle robuste.' },
  { icon: 'i-lucide-percent', title: 'Rendement brut & net', text: 'Calcul automatique en intégrant toutes les charges : taxe foncière, copro, gestion, assurance.' },
  { icon: 'i-lucide-wallet', title: 'Cashflow mensuel', text: 'Visualisez ce qu’il reste chaque mois après remboursement du crédit et paiement des charges.' },
  { icon: 'i-lucide-landmark', title: 'Simulation de crédit', text: 'Mensualités, durée, taux, apport — simulez votre financement bancaire en temps réel.' },
  { icon: 'i-lucide-receipt', title: 'Fiscalité estimée', text: 'Comparaison LMNP, location nue, micro-BIC pour choisir le meilleur régime fiscal.' },
  { icon: 'i-lucide-clock', title: 'Retour sur investissement', text: 'Combien d’années pour rembourser votre investissement avec les loyers perçus.' },
]

const stats = [
  { value: '50k+', label: 'Annonces analysées' },
  { value: '87%', label: 'Précision (R²)' },
  { value: '~112€', label: 'Erreur moyenne' },
  { value: '6k+', label: 'Villes couvertes' },
]
</script>

<template>
  <div class="bg-gray-50 dark:bg-transparent">
    <section class="py-20 px-4">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-5xl font-extrabold text-gray-950 dark:text-white leading-tight">
          Analysez la rentabilité de
          n'importe quel bien
          en quelques secondes
        </h1>
        <p class="mt-6 text-lg text-gray-700 dark:text-gray-400 max-w-2xl mx-auto leading-relaxed">
          Rentvly estime automatiquement le loyer grâce à l'IA, puis calcule le rendement,
          le cashflow et la fiscalité de votre investissement locatif.
        </p>
        <div class="mt-8 flex justify-center gap-4">
          <UButton to="/listings" size="lg" icon="i-lucide-building" color="neutral" variant="solid" class="shadow-md">
            Voir les biens en vente
          </UButton>
          <UButton
            size="lg"
            icon="i-lucide-chart-column"
            variant="outline"
            color="neutral"
            class="bg-[#f4f5f7] shadow-md dark:bg-transparent dark:shadow-none"
            @click="goStats"
          >
            Statistiques du marché
          </UButton>
        </div>
      </div>
    </section>

    <section class="py-16 px-4 bg-[#eef0f3] dark:bg-gray-950">
      <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-center text-gray-950 dark:text-white mb-12">
          Comment ça marche ?
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div
            v-for="step in steps"
            :key="step.title"
            class="text-center p-5 rounded-2xl bg-[#f4f5f7] border border-gray-300/80 shadow-[0_4px_14px_rgba(15,23,42,0.1)] dark:bg-transparent dark:border-transparent dark:shadow-none"
          >
            <div class="w-14 h-14 rounded-full bg-[#eef0f3] dark:bg-gray-900 border border-gray-300/80 dark:border-gray-800 shadow-sm flex items-center justify-center mx-auto mb-4">
              <UIcon :name="step.icon" class="size-7 text-gray-950 dark:text-white" />
            </div>
            <h3 class="font-semibold text-gray-950 dark:text-white mb-2">{{ step.title }}</h3>
            <p class="text-sm text-gray-700 dark:text-gray-400 leading-relaxed">
              {{ step.text }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-16 px-4">
      <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-center text-gray-950 dark:text-white mb-12">
          Ce que Rentvly calcule pour vous
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="item in features"
            :key="item.title"
            class="p-6 rounded-xl border border-gray-300/80 bg-[#f4f5f7] shadow-[0_4px_16px_rgba(15,23,42,0.1)] dark:bg-transparent dark:border-gray-800 dark:shadow-none"
          >
            <UIcon :name="item.icon" class="size-6 text-gray-950 dark:text-white mb-3" />
            <h3 class="font-semibold text-gray-950 dark:text-white mb-1">{{ item.title }}</h3>
            <p class="text-sm text-gray-700 dark:text-gray-400 leading-relaxed">{{ item.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-16 px-4 bg-[#eef0f3] dark:bg-gray-950">
      <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div
            v-for="stat in stats"
            :key="stat.label"
            class="text-center p-4 rounded-xl bg-[#f4f5f7] border border-gray-300/80 shadow-[0_3px_12px_rgba(15,23,42,0.09)] dark:bg-transparent dark:border-transparent dark:shadow-none"
          >
            <p class="text-3xl font-bold text-gray-950 dark:text-white">{{ stat.value }}</p>
            <p class="text-sm text-gray-700 dark:text-gray-400 mt-1 font-medium">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
