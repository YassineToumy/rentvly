<script setup lang="ts">
const props = withDefaults(defineProps<{
  labels: string[]
  values: number[]
  color?: string
  formatTick?: (n: number) => string
}>(), {
  color: '#22c55e',
})

const width = 640
const height = 220
const pad = { top: 16, right: 12, bottom: 36, left: 48 }

const points = computed(() => {
  const vals = props.values
  if (!vals.length) return []
  const min = Math.min(...vals)
  const max = Math.max(...vals)
  const span = max - min || 1
  const innerW = width - pad.left - pad.right
  const innerH = height - pad.top - pad.bottom

  return vals.map((v, i) => {
    const x = pad.left + (vals.length === 1 ? innerW / 2 : (i / (vals.length - 1)) * innerW)
    const y = pad.top + innerH - ((v - min) / span) * innerH
    return { x, y, v }
  })
})

const linePath = computed(() => {
  if (points.value.length < 2) return ''
  return points.value.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ')
})

const areaPath = computed(() => {
  if (points.value.length < 2) return ''
  const bottom = height - pad.bottom
  const first = points.value[0]
  const last = points.value[points.value.length - 1]
  return `${linePath.value} L${last.x.toFixed(1)},${bottom} L${first.x.toFixed(1)},${bottom} Z`
})

const xLabels = computed(() => {
  const labels = props.labels
  if (labels.length <= 8) return labels.map((l, i) => ({ label: l, i }))
  const step = Math.ceil(labels.length / 6)
  return labels.map((l, i) => ({ label: l, i })).filter((_, i) => i % step === 0 || i === labels.length - 1)
})

function xForIndex(i: number) {
  const innerW = width - pad.left - pad.right
  if (props.values.length <= 1) return pad.left + innerW / 2
  return pad.left + (i / (props.values.length - 1)) * innerW
}

const yTicks = computed(() => {
  const vals = props.values
  if (!vals.length) return []
  const min = Math.min(...vals)
  const max = Math.max(...vals)
  const span = max - min || 1
  const innerH = height - pad.top - pad.bottom
  return [0, 0.5, 1].map((t) => {
    const v = min + span * (1 - t)
    return {
      y: pad.top + innerH * t,
      label: props.formatTick ? props.formatTick(v) : String(Math.round(v)),
    }
  })
})
</script>

<template>
  <div class="w-full">
    <svg
      v-if="points.length > 1"
      viewBox="0 0 640 220"
      class="w-full h-auto"
      role="img"
    >
      <line
        v-for="tick in yTicks"
        :key="tick.y"
        :x1="pad.left"
        :x2="width - pad.right"
        :y1="tick.y"
        :y2="tick.y"
        class="stroke-gray-200 dark:stroke-gray-800"
        stroke-width="1"
      />
      <text
        v-for="tick in yTicks"
        :key="'l'+tick.y"
        :x="pad.left - 8"
        :y="tick.y + 4"
        text-anchor="end"
        class="fill-gray-500 text-[10px]"
      >
        {{ tick.label }}
      </text>
      <path :d="areaPath" :fill="color" fill-opacity="0.12" />
      <path :d="linePath" fill="none" :stroke="color" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
      <circle
        v-for="(p, i) in points"
        :key="i"
        :cx="p.x"
        :cy="p.y"
        r="2.5"
        :fill="color"
      />
      <text
        v-for="item in xLabels"
        :key="item.i"
        :x="xForIndex(item.i)"
        :y="height - 10"
        text-anchor="middle"
        class="fill-gray-500 text-[10px]"
      >
        {{ item.label }}
      </text>
    </svg>
    <p v-else class="text-sm text-gray-500 py-10 text-center">Pas assez de points pour tracer une courbe.</p>
  </div>
</template>
