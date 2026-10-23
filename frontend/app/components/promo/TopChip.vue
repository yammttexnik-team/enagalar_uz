<script setup lang="ts">
const { t } = useI18n()
</script>

<template>
  <span class="inline-flex items-center gap-1 rounded-full bg-brand-500 px-2.5 py-1 text-xs font-bold text-white">
    <UIcon name="i-lucide-trending-up" class="size-3.5" aria-hidden="true" />{{ t('promo.top') }}
  </span>
</template>
