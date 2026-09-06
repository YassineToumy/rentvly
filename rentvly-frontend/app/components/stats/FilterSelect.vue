<script setup lang="ts">
export type FilterSelectItem = {
  label: string
  value: string
}

const model = defineModel<string>({ default: '' })

const props = withDefaults(defineProps<{
  label: string
  items: FilterSelectItem[]
  placeholder?: string
  icon?: string
  disabled?: boolean
}>(), {
  placeholder: 'Tous',
  icon: undefined,
  disabled: false,
})

const hasValue = computed(() => Boolean(model.value))

const selectItems = computed(() =>
  props.items
    .filter(i => i.value !== '')
    .map(i => ({ label: i.label, value: i.value })),
)

const selectValue = computed({
  get: () => (model.value === '' ? undefined : model.value),
  set: (v: string | undefined | null) => {
    model.value = v ?? ''
  },
})

function clear() {
  if (props.disabled) return
  model.value = ''
}
</script>

<template>
  <div class="flex flex-col gap-1.5 min-w-0">
    <div class="flex items-center justify-between gap-2 min-h-[18px]">
      <label class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate">
        {{ label }}
      </label>
      <button
        v-if="hasValue"
        type="button"
        class="text-[11px] font-medium text-primary-600 dark:text-primary-400 hover:underline shrink-0 disabled:opacity-50"
        :disabled="disabled"
        @click="clear"
      >
        Effacer
      </button>
    </div>

    <div
      class="group relative rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm transition-colors focus-within:border-primary-500/60 focus-within:ring-2 focus-within:ring-primary-500/15"
      :class="disabled ? 'opacity-60 pointer-events-none' : 'hover:border-gray-300 dark:hover:border-gray-700'"
    >
      <USelect
        v-model="selectValue"
        :items="selectItems"
        value-key="value"
        :placeholder="placeholder"
        :icon="icon"
        :disabled="disabled"
        size="md"
        class="w-full"
        :ui="{
          base: 'w-full ring-0 shadow-none border-0 bg-transparent focus:ring-0',
        }"
      />

      <button
        v-if="hasValue"
        type="button"
        class="absolute right-8 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center size-6 rounded-md text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        :disabled="disabled"
        aria-label="Désélectionner"
        @click.stop.prevent="clear"
      >
        <UIcon name="i-lucide-x" class="size-3.5" />
      </button>
    </div>
  </div>
</template>
