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
      ? 'border-emerald-700 bg-emerald-50 shadow-[0_4px_14px_rgba(15,23,42,0.1)] dark:border-primary-500 dark:bg-primary-500/5'
      : 'app-card hover:border-emerald-600/50 cursor-pointer'"
    @click="handleClick"
  >
    <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-primary-500/10 flex items-center justify-center mb-3">
      <UIcon :name="icon" class="size-5 text-emerald-800 dark:text-primary-400" />
    </div>
    <p class="text-2xl font-bold text-gray-950 dark:text-white">{{ value }}</p>
    <p class="text-xs text-gray-700 dark:text-gray-400 mt-1">{{ label }}</p>
    <p v-if="trend" class="text-xs text-emerald-800 dark:text-primary-400 mt-1">{{ trend }}</p>
  </component>
</template>
