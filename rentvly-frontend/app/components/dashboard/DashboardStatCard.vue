<script setup lang="ts">
const props = defineProps<{
  label: string
  value: string
  icon: string
  trend?: string
  to?: string
  active?: boolean
}>()

const emit = defineEmits<{
  click: []
}>()

function handleClick() {
  if (props.to) return
  emit('click')
}
</script>

<template>
  <component
    :is="to ? 'NuxtLink' : 'button'"
    :to="to"
    type="button"
    class="block w-full text-left p-5 rounded-xl border transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
    :class="active
      ? 'border-primary-500 bg-primary-500/5 shadow-sm'
      : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800/60 hover:border-primary-500/40 hover:shadow-md cursor-pointer'"
    @click="handleClick"
  >
    <div class="w-10 h-10 rounded-lg bg-primary-500/10 flex items-center justify-center mb-3">
      <UIcon :name="icon" class="size-5 text-primary-400" />
    </div>
    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
    <p class="text-xs text-gray-600 dark:text-gray-500 mt-1">{{ label }}</p>
    <p v-if="trend" class="text-xs text-primary-400 mt-1">{{ trend }}</p>
  </component>
</template>
