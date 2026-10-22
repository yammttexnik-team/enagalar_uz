<script setup lang="ts">
// Oʻqilmagan xabarlar: brauzer sarlavhasida "(3) …"
const chat = useChatStore()
// <html lang>, hreflang va og:locale
const i18nHead = useLocaleHead({ seo: true })
useHead(() => ({
  htmlAttrs: { lang: i18nHead.value.htmlAttrs?.lang },
  link: i18nHead.value.link,
  meta: i18nHead.value.meta,
}))
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
