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
  disabled?: boolean
}>(), {
  placeholder: 'Tous',
  disabled: false,
})

const open = ref(false)
const root = ref<HTMLElement | null>(null)

const options = computed(() => props.items.filter(i => i.value !== '' && i.value != null))
const selectedLabel = computed(
  () => options.value.find(i => String(i.value) === String(model.value))?.label ?? props.placeholder,
)

function select(value: string) {
  model.value = value
  open.value = false
}

function onClickOutside(e: MouseEvent) {
  if (root.value && !root.value.contains(e.target as Node)) open.value = false
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
  <div ref="root" class="flex flex-col gap-1 min-w-0 relative">
    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
    </label>
    <button
      type="button"
      :disabled="disabled"
      class="w-full h-9 px-2 text-sm text-left flex items-center justify-between gap-2 border border-gray-300 dark:border-gray-600 rounded bg-[#f4f5f7] dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm dark:shadow-none focus:outline-none focus:border-gray-500 dark:focus:border-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
      @click="open = !open"
    >
      <span class="truncate" :class="{ 'text-gray-400 dark:text-gray-500': !model }">{{ selectedLabel }}</span>
      <UIcon
        name="i-lucide-chevron-down"
        class="size-4 shrink-0 text-gray-400 transition-transform"
        :class="{ 'rotate-180': open }"
      />
    </button>

    <div
      v-if="open"
      class="absolute z-20 top-full left-0 right-0 mt-1 max-h-56 overflow-y-auto rounded border border-gray-300 dark:border-gray-700 bg-[#f4f5f7] dark:bg-gray-900 shadow-lg py-1"
    >
      <button
        type="button"
        class="w-full text-left px-3 py-1.5 text-sm hover:bg-gray-200/70 dark:hover:bg-gray-800"
        :class="{ 'font-semibold text-emerald-800 dark:text-primary-400': !model }"
        @click="select('')"
      >
        {{ placeholder }}
      </button>
      <button
        v-for="item in options"
        :key="String(item.value)"
        type="button"
        class="w-full text-left px-3 py-1.5 text-sm hover:bg-gray-200/70 dark:hover:bg-gray-800"
        :class="{ 'font-semibold text-emerald-800 dark:text-primary-400': String(model) === String(item.value) }"
        @click="select(String(item.value))"
      >
        {{ item.label }}
      </button>
    </div>
  </div>
</template>
