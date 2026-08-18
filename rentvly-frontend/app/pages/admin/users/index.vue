<script setup lang="ts">
import type { AdminUser } from '~/composables/useAdmin'

const { fetchUsers, createUser, updateUser, deleteUser, loading, error } = useAdmin()

const users = ref<AdminUser[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const search = ref('')
const roleFilter = ref<string | undefined>()
const page = ref(1)

const modalOpen = ref(false)
const deleteOpen = ref(false)
const editing = ref<AdminUser | null>(null)
const deleting = ref<AdminUser | null>(null)
const formError = ref<string | null>(null)
const saving = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: 'investor' as 'admin' | 'investor',
})

const roleOptions = [
  { label: 'Investisseur', value: 'investor' },
  { label: 'Administrateur', value: 'admin' },
]

async function load() {
  try {
    const res = await fetchUsers({
      search: search.value || undefined,
      role: roleFilter.value || undefined,
      page: page.value,
      per_page: 15,
    })
    users.value = res.data
    meta.value = res.meta
  } catch {}
}

onMounted(load)

watch([search, roleFilter], () => {
  page.value = 1
  load()
})

watch(page, load)

function openCreate() {
  editing.value = null
  form.name = ''
  form.email = ''
  form.password = ''
  form.role = 'investor'
  formError.value = null
  modalOpen.value = true
}

function openEdit(user: AdminUser) {
  editing.value = user
  form.name = user.name
  form.email = user.email
  form.password = ''
  form.role = user.role
  formError.value = null
  modalOpen.value = true
}

function openDelete(user: AdminUser) {
  deleting.value = user
  deleteOpen.value = true
}

async function save() {
  saving.value = true
  formError.value = null
  try {
    if (editing.value) {
      const body: Record<string, any> = {
        name: form.name,
        email: form.email,
        role: form.role,
      }
      if (form.password) body.password = form.password
      await updateUser(editing.value.id, body)
    } else {
      if (!form.password) {
        formError.value = 'Le mot de passe est obligatoire.'
        return
      }
      await createUser({ ...form })
    }
    modalOpen.value = false
    await load()
  } catch (e: any) {
    formError.value = e?.data?.error || e?.data?.message || 'Enregistrement impossible.'
  } finally {
    saving.value = false
  }
}

async function confirmDelete() {
  if (!deleting.value) return
  saving.value = true
  try {
    await deleteUser(deleting.value.id)
    deleteOpen.value = false
    deleting.value = null
    await load()
  } catch (e: any) {
    formError.value = e?.data?.error || e?.data?.message || 'Suppression impossible.'
  } finally {
    saving.value = false
  }
}

function formatDate(date: string | null) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Comptes</h1>
        <p class="text-sm text-slate-500 mt-1">CRUD des utilisateurs et rôles.</p>
      </div>
      <UButton color="blue" icon="i-lucide-user-plus" @click="openCreate">
        Nouveau compte
      </UButton>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <UInput
        v-model="search"
        icon="i-lucide-search"
        placeholder="Rechercher nom ou email…"
        class="sm:max-w-xs"
      />
      <USelect
        v-model="roleFilter"
        :items="[{ label: 'Tous les rôles', value: undefined }, ...roleOptions]"
        placeholder="Rôle"
        class="sm:w-48"
      />
    </div>

    <UAlert v-if="error" color="error" variant="soft" :title="error" class="mb-2" />

    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200 dark:border-slate-800">
              <th class="px-5 py-3 font-medium">Utilisateur</th>
              <th class="px-5 py-3 font-medium">Rôle</th>
              <th class="px-5 py-3 font-medium">Estimations</th>
              <th class="px-5 py-3 font-medium">Créé le</th>
              <th class="px-5 py-3 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-if="loading && !users.length">
              <td colspan="5" class="px-5 py-12 text-center">
                <UIcon name="i-lucide-loader-2" class="size-6 text-blue-500 animate-spin inline" />
              </td>
            </tr>
            <tr v-else-if="!users.length">
              <td colspan="5" class="px-5 py-12 text-center text-slate-500">Aucun compte trouvé</td>
            </tr>
            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
              <td class="px-5 py-3.5">
                <p class="font-medium">{{ user.name }}</p>
                <p class="text-xs text-slate-500">{{ user.email }}</p>
              </td>
              <td class="px-5 py-3.5">
                <span
                  class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase"
                  :class="user.role === 'admin'
                    ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400'
                    : 'bg-slate-500/10 text-slate-600 dark:text-slate-400'"
                >
                  {{ user.role }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">{{ user.estimations_count }}</td>
              <td class="px-5 py-3.5 text-slate-500">{{ formatDate(user.created_at) }}</td>
              <td class="px-5 py-3.5">
                <div class="flex justify-end gap-1">
                  <UButton variant="ghost" color="neutral" size="xs" icon="i-lucide-pencil" @click="openEdit(user)" />
                  <UButton variant="ghost" color="error" size="xs" icon="i-lucide-trash-2" @click="openDelete(user)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="meta.last_page > 1" class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <p class="text-xs text-slate-500">{{ meta.total }} compte(s)</p>
        <div class="flex gap-2">
          <UButton size="xs" variant="soft" color="neutral" :disabled="page <= 1" @click="page--">Préc.</UButton>
          <span class="text-xs self-center text-slate-500">{{ page }} / {{ meta.last_page }}</span>
          <UButton size="xs" variant="soft" color="neutral" :disabled="page >= meta.last_page" @click="page++">Suiv.</UButton>
        </div>
      </div>
    </div>

    <UModal v-model:open="modalOpen">
      <template #content>
        <div class="p-6 space-y-4">
          <h3 class="text-lg font-semibold">{{ editing ? 'Modifier le compte' : 'Nouveau compte' }}</h3>
          <UAlert v-if="formError" color="error" variant="soft" :title="formError" />
          <UFormField label="Nom">
            <UInput v-model="form.name" class="w-full" />
          </UFormField>
          <UFormField label="Email">
            <UInput v-model="form.email" type="email" class="w-full" />
          </UFormField>
          <UFormField :label="editing ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe'">
            <UInput v-model="form.password" type="password" class="w-full" />
          </UFormField>
          <UFormField label="Rôle">
            <USelect v-model="form.role" :items="roleOptions" class="w-full" />
          </UFormField>
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
          <h3 class="text-lg font-semibold">Supprimer le compte</h3>
          <p class="text-sm text-slate-500">
            Confirmer la suppression de <strong>{{ deleting?.name }}</strong> ({{ deleting?.email }}) ?
          </p>
          <UAlert v-if="formError" color="error" variant="soft" :title="formError" />
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" color="neutral" @click="deleteOpen = false">Annuler</UButton>
            <UButton color="error" :loading="saving" @click="confirmDelete">Supprimer</UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
