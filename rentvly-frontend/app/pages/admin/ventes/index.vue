<script setup lang="ts">
import type { AdminVente } from '~/composables/useAdmin'

const { fetchVentes, createVente, updateVente, deleteVente, loading, error } = useAdmin()

const ventes = ref<AdminVente[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const search = ref('')
const typeFilter = ref<string | undefined>()
const page = ref(1)

const modalOpen = ref(false)
const deleteOpen = ref(false)
const editing = ref<AdminVente | null>(null)
const deleting = ref<AdminVente | null>(null)
const formError = ref<string | null>(null)
const saving = ref(false)

const emptyForm = () => ({
  title: '',
  description: '',
  property_type: 'flat',
  price: 0,
  price_per_sqm: null as number | null,
  surface_area: null as number | null,
  rooms_quantity: null as number | null,
  city: '',
  postal_code: '',
  department_code: '',
  district_name: '',
  owner_name: '',
  is_new_property: false,
  is_pro: false,
  photos_text: '',
})

const form = reactive(emptyForm())

const typeOptions = [
  { label: 'Appartement', value: 'flat' },
  { label: 'Maison', value: 'house' },
]

async function load() {
  try {
    const res = await fetchVentes({
      search: search.value || undefined,
      type: typeFilter.value || undefined,
      page: page.value,
      per_page: 15,
    })
    ventes.value = res.data
    meta.value = res.meta
  } catch {}
}

onMounted(load)

watch([search, typeFilter], () => {
  page.value = 1
  load()
})

watch(page, load)

function openCreate() {
  editing.value = null
  Object.assign(form, emptyForm())
  formError.value = null
  modalOpen.value = true
}

function openEdit(vente: AdminVente) {
  editing.value = vente
  Object.assign(form, {
    title: vente.title || '',
    description: vente.description || '',
    property_type: vente.property_type === 'house' ? 'house' : 'flat',
    price: vente.price || 0,
    price_per_sqm: vente.price_per_sqm ?? null,
    surface_area: vente.surface_area ?? null,
    rooms_quantity: vente.rooms_quantity ?? null,
    city: vente.city || '',
    postal_code: vente.postal_code || '',
    department_code: vente.department_code || '',
    district_name: vente.district_name || '',
    owner_name: vente.owner_name || '',
    is_new_property: !!vente.is_new_property,
    is_pro: !!vente.is_pro,
    photos_text: (vente.photos || []).join('\n'),
  })
  formError.value = null
  modalOpen.value = true
}

async function openEditById(id: number) {
  const { fetchVente } = useAdmin()
  try {
    const full = await fetchVente(id)
    openEdit(full)
  } catch {}
}

function openDelete(vente: AdminVente) {
  deleting.value = vente
  deleteOpen.value = true
}

function payload() {
  const photos = form.photos_text
    .split('\n')
    .map(s => s.trim())
    .filter(Boolean)

  return {
    title: form.title,
    description: form.description || null,
    property_type: form.property_type,
    price: Number(form.price) || 0,
    price_per_sqm: form.price_per_sqm,
    surface_area: form.surface_area,
    rooms_quantity: form.rooms_quantity,
    city: form.city,
    postal_code: form.postal_code || null,
    department_code: form.department_code || null,
    district_name: form.district_name || null,
    owner_name: form.owner_name || null,
    is_new_property: form.is_new_property,
    is_pro: form.is_pro,
    photos,
  }
}

async function save() {
  saving.value = true
  formError.value = null
  try {
    if (editing.value) {
      await updateVente(editing.value.id, payload())
    } else {
      await createVente(payload())
    }
    modalOpen.value = false
    await load()
  } catch (e: any) {
    formError.value = e?.data?.message || e?.data?.error || 'Enregistrement impossible.'
  } finally {
    saving.value = false
  }
}

async function confirmDelete() {
  if (!deleting.value) return
  saving.value = true
  try {
    await deleteVente(deleting.value.id)
    deleteOpen.value = false
    deleting.value = null
    await load()
  } catch (e: any) {
    formError.value = e?.data?.error || e?.data?.message || 'Suppression impossible.'
  } finally {
    saving.value = false
  }
}

function formatCurrency(value: number | null) {
  if (value == null) return '—'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
  }).format(value)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Annonces</h1>
        <p class="text-sm text-slate-500 mt-1">CRUD des biens publiés sur le site.</p>
      </div>
      <UButton color="blue" icon="i-lucide-plus" @click="openCreate">
        Nouvelle annonce
      </UButton>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <UInput
        v-model="search"
        icon="i-lucide-search"
        placeholder="Titre, ville, code postal…"
        class="sm:max-w-xs"
      />
      <USelect
        v-model="typeFilter"
        :items="[{ label: 'Tous les types', value: undefined }, ...typeOptions]"
        placeholder="Type"
        class="sm:w-48"
      />
    </div>

    <UAlert v-if="error" color="error" variant="soft" :title="error" />

    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200 dark:border-slate-800">
              <th class="px-5 py-3 font-medium">Annonce</th>
              <th class="px-5 py-3 font-medium">Type</th>
              <th class="px-5 py-3 font-medium">Prix</th>
              <th class="px-5 py-3 font-medium">Surface</th>
              <th class="px-5 py-3 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-if="loading && !ventes.length">
              <td colspan="5" class="px-5 py-12 text-center">
                <UIcon name="i-lucide-loader-2" class="size-6 text-blue-500 animate-spin inline" />
              </td>
            </tr>
            <tr v-else-if="!ventes.length">
              <td colspan="5" class="px-5 py-12 text-center text-slate-500">Aucune annonce</td>
            </tr>
            <tr v-for="vente in ventes" :key="vente.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden shrink-0">
                    <img
                      v-if="vente.image"
                      :src="vente.image"
                      alt=""
                      class="w-full h-full object-cover"
                    >
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <UIcon name="i-lucide-image" class="size-4 text-slate-400" />
                    </div>
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium truncate max-w-[280px]">{{ vente.title || 'Sans titre' }}</p>
                    <p class="text-xs text-slate-500">{{ vente.city }} {{ vente.postal_code }}</p>
                  </div>
                </div>
              </td>
              <td class="px-5 py-3.5 capitalize text-slate-600 dark:text-slate-300">{{ vente.property_type }}</td>
              <td class="px-5 py-3.5 font-semibold text-blue-600 dark:text-blue-400">{{ formatCurrency(vente.price) }}</td>
              <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">
                {{ vente.surface_area != null ? `${vente.surface_area} m²` : '—' }}
                <span v-if="vente.rooms_quantity" class="text-slate-400"> · {{ vente.rooms_quantity }} p.</span>
              </td>
              <td class="px-5 py-3.5">
                <div class="flex justify-end gap-1">
                  <UButton
                    variant="ghost"
                    color="neutral"
                    size="xs"
                    icon="i-lucide-pencil"
                    @click="openEditById(vente.id)"
                  />
                  <UButton
                    variant="ghost"
                    color="error"
                    size="xs"
                    icon="i-lucide-trash-2"
                    @click="openDelete(vente)"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="meta.last_page > 1" class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <p class="text-xs text-slate-500">{{ meta.total }} annonce(s)</p>
        <div class="flex gap-2">
          <UButton size="xs" variant="soft" color="neutral" :disabled="page <= 1" @click="page--">Préc.</UButton>
          <span class="text-xs self-center text-slate-500">{{ page }} / {{ meta.last_page }}</span>
          <UButton size="xs" variant="soft" color="neutral" :disabled="page >= meta.last_page" @click="page++">Suiv.</UButton>
        </div>
      </div>
    </div>

    <UModal v-model:open="modalOpen">
      <template #content>
        <div class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
          <h3 class="text-lg font-semibold">{{ editing ? 'Modifier l\'annonce' : 'Nouvelle annonce' }}</h3>
          <UAlert v-if="formError" color="error" variant="soft" :title="formError" />

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <UFormField label="Titre" class="sm:col-span-2">
              <UInput v-model="form.title" class="w-full" />
            </UFormField>
            <UFormField label="Description" class="sm:col-span-2">
              <UTextarea v-model="form.description" :rows="3" class="w-full" />
            </UFormField>
            <UFormField label="Type">
              <USelect v-model="form.property_type" :items="typeOptions" class="w-full" />
            </UFormField>
            <UFormField label="Prix (€)">
              <UInput v-model.number="form.price" type="number" class="w-full" />
            </UFormField>
            <UFormField label="Surface (m²)">
              <UInput v-model.number="form.surface_area" type="number" class="w-full" />
            </UFormField>
            <UFormField label="Pièces">
              <UInput v-model.number="form.rooms_quantity" type="number" class="w-full" />
            </UFormField>
            <UFormField label="Ville">
              <UInput v-model="form.city" class="w-full" />
            </UFormField>
            <UFormField label="Code postal">
              <UInput v-model="form.postal_code" class="w-full" />
            </UFormField>
            <UFormField label="Département">
              <UInput v-model="form.department_code" class="w-full" />
            </UFormField>
            <UFormField label="Quartier">
              <UInput v-model="form.district_name" class="w-full" />
            </UFormField>
            <UFormField label="Annonceur">
              <UInput v-model="form.owner_name" class="w-full" />
            </UFormField>
            <UFormField label="Photos (URLs, une par ligne)" class="sm:col-span-2">
              <UTextarea v-model="form.photos_text" :rows="3" class="w-full" placeholder="https://…" />
            </UFormField>
            <div class="sm:col-span-2 flex flex-wrap gap-4">
              <label class="flex items-center gap-2 text-sm">
                <input v-model="form.is_new_property" type="checkbox" class="rounded border-slate-300 text-blue-600">
                Neuf
              </label>
              <label class="flex items-center gap-2 text-sm">
                <input v-model="form.is_pro" type="checkbox" class="rounded border-slate-300 text-blue-600">
                Pro
              </label>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <UButton variant="ghost" color="neutral" @click="modalOpen = false">Annuler</UButton>
            <UButton color="blue" :loading="saving" @click="save">Enregistrer</UButton>
          </div>
        </div>
      </template>
    </UModal>

    <UModal v-model:open="deleteOpen">
      <template #content>
        <div class="p-6 space-y-4">
          <h3 class="text-lg font-semibold">Supprimer l'annonce</h3>
          <p class="text-sm text-slate-500">
            Confirmer la suppression de <strong>{{ deleting?.title }}</strong> ?
          </p>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" color="neutral" @click="deleteOpen = false">Annuler</UButton>
            <UButton color="error" :loading="saving" @click="confirmDelete">Supprimer</UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
