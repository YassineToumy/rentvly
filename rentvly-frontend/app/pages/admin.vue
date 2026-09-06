<script setup lang="ts">
definePageMeta({
  layout: 'admin',
  middleware: 'admin',
})

useHead({
  link: [{ rel: 'icon', type: 'image/x-icon', href: '/logo_rentvly_admin.ico', key: 'favicon' }],
})
</script>

<template>
  <NuxtPage />
</template>
