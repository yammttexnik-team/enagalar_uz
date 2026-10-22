<script setup lang="ts">
const { t } = useI18n()
const route = useRoute()
definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('chat.seo'), robots: 'noindex' })
const activeId = computed(() => (route.params.id ? Number(route.params.id) : null))
</script>

<template>
  <ClientOnly>
    <div class="surface grid overflow-hidden p-0! lg:h-[calc(100dvh-8.5rem)] lg:min-h-[540px] lg:grid-cols-[340px_minmax(0,1fr)] xl:grid-cols-[360px_minmax(0,1fr)]">
      <ChatConversationList :active-id="activeId" :class="activeId ? 'max-lg:hidden' : ''" class="lg:h-full" />
      <NuxtPage />
    </div>
  </ClientOnly>
</template>
