<script setup lang="ts">
// Oʻqilmagan xabarlar: brauzer sarlavhasida "(3) …"
const chat = useChatStore()
useHead(() => {
  const n = chat.unreadTotal
  return { titleTemplate: (title?: string) => (n > 0 ? `(${n}) ${title ?? 'enagalar.uz'}` : (title ?? 'enagalar.uz')) }
})
</script>

<template>
  <UApp>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>
