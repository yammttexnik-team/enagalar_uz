<script setup lang="ts">
/** Telegram kanaldan olingan e'lon uchun oddiy rasm (foto o'rniga): vakansiya — uy va bola, enaga — yurak va qo'l. */
withDefaults(defineProps<{ type: 'vacancy' | 'nanny', size?: 'sm' | 'lg' }>(), { size: 'sm' })
</script>

<template>
  <svg
    viewBox="0 0 120 120"
    :class="size === 'lg' ? 'size-28 sm:size-32' : 'size-16'"
    class="shrink-0"
    role="img"
    aria-hidden="true"
    focusable="false"
  >
    <rect width="120" height="120" rx="28" class="fill-brand-50 dark:fill-brand-950" />
    <circle cx="96" cy="24" r="10" class="fill-apricot-200 dark:fill-apricot-800" />
    <template v-if="type === 'vacancy'">
      <!-- Uy -->
      <path d="M26 58 60 30l34 28" class="fill-none stroke-brand-500 dark:stroke-brand-300" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M34 54v38h52V54" class="fill-white stroke-brand-500 dark:fill-brand-900 dark:stroke-brand-300" stroke-width="6" stroke-linejoin="round" />
      <!-- Bola -->
      <circle cx="60" cy="66" r="8" class="fill-apricot-300 dark:fill-apricot-400" />
      <path d="M47 92c0-9 6-14 13-14s13 5 13 14" class="fill-apricot-400 dark:fill-apricot-500" />
      <circle cx="57" cy="65" r="1.4" class="fill-sand-800" />
      <circle cx="63" cy="65" r="1.4" class="fill-sand-800" />
    </template>
    <template v-else>
      <!-- Ochiq kaft va yurak -->
      <path d="M60 52c-4-8-17-8-17 2 0 8 17 18 17 18s17-10 17-18c0-10-13-10-17-2Z" class="fill-apricot-400 dark:fill-apricot-500" />
      <path d="M24 78c8-2 16 2 22 6h16c4 0 4 6 0 6H50m12 0h18l14-10c4-3 8 2 5 5L82 96H48l-14-6" class="fill-none stroke-brand-500 dark:stroke-brand-300" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
      <circle cx="40" cy="36" r="4" class="fill-brand-200 dark:fill-brand-700" />
      <circle cx="82" cy="40" r="3" class="fill-brand-200 dark:fill-brand-700" />
    </template>
  </svg>
</template>
