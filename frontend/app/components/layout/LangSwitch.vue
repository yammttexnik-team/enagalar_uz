<script setup lang="ts">
const { locale, locales } = useI18n()
const switchLocalePath = useSwitchLocalePath()
const { t } = useI18n()
</script>

<template>
  <nav :aria-label="t('nav.language')" class="inline-flex items-center rounded-xl bg-elevated p-0.5 text-sm font-semibold">
    <NuxtLink
      v-for="l in locales"
      :key="l.code"
      :to="switchLocalePath(l.code)"
      :lang="l.code"
      :aria-current="l.code === locale ? 'true' : undefined"
      class="rounded-[10px] px-2.5 py-1.5 uppercase transition-colors focus-visible:outline-2 focus-visible:outline-primary"
      :class="l.code === locale ? 'bg-default text-highlighted shadow-sm' : 'text-muted hover:text-highlighted'"
    >
      {{ l.code }}
    </NuxtLink>
  </nav>
</template>
