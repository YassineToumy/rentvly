<template>
  <ColorScheme>
    <UApp>
      <NuxtRouteAnnouncer />
      <NuxtLayout>
        <NuxtPage :key="$route.fullPath" />
      </NuxtLayout>
    </UApp>
  </ColorScheme>
</template>