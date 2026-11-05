<script setup lang="ts">
import type { ImportedItem } from '~/components/imported/Card.vue'

defineProps<{ nanny: ImportedItem | null, vacancy: ImportedItem | null }>()
const { t } = useI18n()
</script>

<template>
  <div class="relative mx-auto w-full max-w-[480px] lg:ml-auto" :class="nanny || vacancy ? '' : 'hidden lg:block'">
    <!-- Belgi shakllaridan fon -->
    <svg class="pointer-events-none absolute inset-0 size-full" viewBox="0 0 400 408" preserveAspectRatio="xMidYMid slice" aria-hidden="true" fill="none">
      <circle cx="196" cy="214" r="168" class="fill-brand-100 dark:fill-brand-900" />
      <path d="M118 112 A112 112 0 1 0 292 322" stroke-width="44" stroke-linecap="round" class="stroke-brand-200 dark:stroke-brand-800" />
      <circle cx="330" cy="70" r="30" class="fill-brand-400 dark:fill-brand-500" />
      <circle cx="80" cy="330" r="14" class="fill-apricot-300 dark:fill-apricot-400" />
    </svg>

    <div class="relative flex flex-col gap-4 px-3 py-10 sm:px-8 sm:py-14">
      <HomeHeroListing v-if="nanny" :item="nanny" kind="nanny" class="sm:mr-10" />
      <HomeHeroListing v-if="vacancy" :item="vacancy" kind="vacancy" class="sm:ml-10" />
      <div v-if="!nanny && !vacancy" class="surface p-6 text-center text-sm text-muted">{{ t('home.hero.visual.empty') }}</div>
    </div>
  </div>
</template>
