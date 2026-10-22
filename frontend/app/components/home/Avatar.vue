<script setup lang="ts">
defineProps<{ tone?: 'brand' | 'apricot' }>()
</script>

<template>
  <svg viewBox="0 0 48 48" class="shrink-0 rounded-full" aria-hidden="true">
    <rect width="48" height="48" :class="tone === 'apricot' ? 'fill-apricot-100 dark:fill-apricot-900' : 'fill-brand-100 dark:fill-brand-800'" />
    <circle cx="24" cy="19" r="8" :class="tone === 'apricot' ? 'fill-apricot-500 dark:fill-apricot-300' : 'fill-brand-500 dark:fill-brand-300'" />
    <path d="M7 48 a17 17 0 0 1 34 0z" :class="tone === 'apricot' ? 'fill-apricot-500 dark:fill-apricot-300' : 'fill-brand-500 dark:fill-brand-300'" />
    <circle cx="38" cy="12" r="4" class="fill-apricot-300 dark:fill-apricot-400" v-if="tone !== 'apricot'" />
  </svg>
</template>
