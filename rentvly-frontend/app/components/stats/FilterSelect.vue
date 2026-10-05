<script setup lang="ts">
export type FilterSelectItem = {
  label: string
  value: string
}

const model = defineModel<string>({ default: '' })

withDefaults(defineProps<{
  label: string
  items: FilterSelectItem[]
  placeholder?: string
  disabled?: boolean
}>(), {
  placeholder: 'Tous',
  disabled: false,
})
</script>

<template>
  <div class="flex flex-col gap-1 min-w-0">
    <label class="text-xs font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
    </label>
    <select
      v-model="model"
      :disabled="disabled"
      class="w-full h-9 px-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-[#f4f5f7] dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm dark:shadow-none focus:outline-none focus:border-gray-500 dark:focus:border-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <option value="">{{ placeholder }}</option>
      <option
        v-for="item in items.filter(i => i.value !== '' && i.value != null)"
        :key="String(item.value)"
        :value="String(item.value)"
      >
        {{ item.label }}
      </option>
    </select>
  </div>
</template>
