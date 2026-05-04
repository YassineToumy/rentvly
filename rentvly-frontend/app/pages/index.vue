<template>
  <div>
    <!-- Hero -->
    <section class="py-20 px-4">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-5xl font-extrabold text-black dark:text-white leading-tight">
          Analysez la rentabilité de
          n'importe quel bien
          en quelques secondes
        </h1>
        <p class="mt-6 text-lg text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
          Rentvly estime automatiquement le loyer grâce à l'IA, puis calcule le rendement,
          le cashflow et la fiscalité de votre investissement locatif.
        </p>
        <div class="mt-8 flex justify-center gap-4">
          <UButton to="/listings" size="lg" icon="i-lucide-building" color="neutral" variant="solid">
            Voir les biens en vente
          </UButton>
          <UButton to="/predict" size="lg" icon="i-lucide-calculator" variant="outline" color="neutral">
            Estimer un loyer
          </UButton>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section class="py-16 px-4 bg-gray-50 dark:bg-gray-950">
      <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-center text-black dark:text-white mb-12">
          Comment ça marche ?
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div class="text-center">
            <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 flex items-center justify-center mx-auto mb-4">
              <UIcon name="i-lucide-home" class="size-7 text-black dark:text-white" />
            </div>
            <h3 class="font-semibold text-black dark:text-white mb-2">1. Décrivez le bien</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Ville, surface, pièces, équipements — les caractéristiques du bien à analyser.
            </p>
          </div>

          <div class="text-center">
            <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 flex items-center justify-center mx-auto mb-4">
              <UIcon name="i-lucide-brain" class="size-7 text-black dark:text-white" />
            </div>
            <h3 class="font-semibold text-black dark:text-white mb-2">2. Estimation du loyer</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Notre IA prédit le loyer mensuel en comparant avec des milliers d'annonces similaires.
            </p>
          </div>

          <div class="text-center">
            <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 flex items-center justify-center mx-auto mb-4">
              <UIcon name="i-lucide-calculator" class="size-7 text-black dark:text-white" />
            </div>
            <h3 class="font-semibold text-black dark:text-white mb-2">3. Simulez le financement</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Prix d'achat, apport, crédit, travaux, charges — ajustez tous les paramètres de votre projet.
            </p>
          </div>

          <div class="text-center">
            <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 flex items-center justify-center mx-auto mb-4">
              <UIcon name="i-lucide-bar-chart-3" class="size-7 text-black dark:text-white" />
            </div>
            <h3 class="font-semibold text-black dark:text-white mb-2">4. Résultats complets</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Rendement brut & net, cashflow mensuel, retour sur investissement et analyse fiscale.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Key metrics -->
    <section class="py-16 px-4">
      <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-center text-black dark:text-white mb-12">
          Ce que Rentvly calcule pour vous
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-xl">
            <UIcon name="i-lucide-euro" class="size-6 text-black dark:text-white mb-3" />
            <h3 class="font-semibold text-black dark:text-white mb-1">Loyer estimé par IA</h3>
            <p class="text-sm text-gray-500">Prédiction du loyer mensuel basée sur 50 000+ annonces et un modèle robuste</p>
          </div>
          <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-xl">
            <UIcon name="i-lucide-percent" class="size-6 text-black dark:text-white mb-3" />
            <h3 class="font-semibold text-black dark:text-white mb-1">Rendement brut & net</h3>
            <p class="text-sm text-gray-500">Calcul automatique en intégrant toutes les charges : taxe foncière, copro, gestion, assurance.</p>
          </div>
          <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-xl">
            <UIcon name="i-lucide-wallet" class="size-6 text-black dark:text-white mb-3" />
            <h3 class="font-semibold text-black dark:text-white mb-1">Cashflow mensuel</h3>
            <p class="text-sm text-gray-500">Visualisez ce qu'il reste chaque mois après remboursement du crédit et paiement des charges.</p>
          </div>
          <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-xl">
            <UIcon name="i-lucide-landmark" class="size-6 text-black dark:text-white mb-3" />
            <h3 class="font-semibold text-black dark:text-white mb-1">Simulation de crédit</h3>
            <p class="text-sm text-gray-500">Mensualités, durée, taux, apport — simulez votre financement bancaire en temps réel.</p>
          </div>
          <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-xl">
            <UIcon name="i-lucide-receipt" class="size-6 text-black dark:text-white mb-3" />
            <h3 class="font-semibold text-black dark:text-white mb-1">Fiscalité estimée</h3>
            <p class="text-sm text-gray-500">Comparaison LMNP, location nue, micro-BIC pour choisir le meilleur régime fiscal.</p>
          </div>
          <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-xl">
            <UIcon name="i-lucide-clock" class="size-6 text-black dark:text-white mb-3" />
            <h3 class="font-semibold text-black dark:text-white mb-1">Retour sur investissement</h3>
            <p class="text-sm text-gray-500">Combien d'années pour rembourser votre investissement avec les loyers perçus.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="py-16 px-4 bg-gray-50 dark:bg-gray-950">
      <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div class="text-center">
            <p class="text-3xl font-bold text-black dark:text-white">50k+</p>
            <p class="text-sm text-gray-500 mt-1">Annonces analysées</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-bold text-black dark:text-white">87%</p>
            <p class="text-sm text-gray-500 mt-1">Précision (R²)</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-bold text-black dark:text-white">~112€</p>
            <p class="text-sm text-gray-500 mt-1">Erreur moyenne</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-bold text-black dark:text-white">6k+</p>
            <p class="text-sm text-gray-500 mt-1">Villes couvertes</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>