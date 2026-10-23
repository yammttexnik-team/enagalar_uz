<script setup lang="ts">
defineProps<{ channel: string }>()
const { t } = useI18n()
</script>

<template>
  <p class="inline-flex flex-wrap items-center gap-1 rounded-full bg-sand-100 px-2.5 py-1 text-xs font-semibold text-sand-700 dark:bg-sand-800 dark:text-sand-200">
    <UIcon name="i-lucide-send" class="size-3.5" aria-hidden="true" />
    {{ t('imported.badge.source') }} · @{{ channel }} · {{ t('imported.badge.unverified') }}
  </p>
</template>
