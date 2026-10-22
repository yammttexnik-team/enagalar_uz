<script setup lang="ts">
const { t } = useI18n()
</script>

<template>
  <div class="hidden place-items-center bg-brand-50/50 px-8 text-center lg:grid dark:bg-brand-950/30">
    <div class="max-w-sm">
      <span class="mx-auto grid size-16 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-message-circle" class="size-8" aria-hidden="true" /></span>
      <h2 class="mt-4 font-display text-xl font-extrabold text-highlighted">{{ t('chat.pick.title') }}</h2>
      <p class="mt-1.5 text-muted">{{ t('chat.pick.text') }}</p>
    </div>
  </div>
</template>
