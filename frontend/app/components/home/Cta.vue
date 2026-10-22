<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
</script>

<template>
  <section class="px-4 pb-16 pt-16 sm:px-6 sm:pb-24 sm:pt-24">
    <div class="relative mx-auto max-w-5xl overflow-hidden rounded-[2rem] bg-brand-500 px-6 py-14 text-center text-white sm:px-12 sm:py-16 dark:bg-brand-800">
      <svg class="pointer-events-none absolute -left-16 -bottom-20 size-72 opacity-40" viewBox="0 0 100 100" fill="none" aria-hidden="true">
        <path d="M30 14 A38 38 0 1 0 82 66" stroke="#006e74" stroke-width="14" stroke-linecap="round" />
      </svg>
      <span class="pointer-events-none absolute right-10 top-8 size-8 rounded-full bg-apricot-300" aria-hidden="true" />
      <div class="relative">
        <h2 class="h1-title text-white">{{ t('home.cta.title') }}</h2>
        <p class="mx-auto mt-4 max-w-xl text-lg text-brand-50">{{ t('home.cta.text') }}</p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
          <UButton size="xl" color="neutral" class="justify-center bg-white! text-brand-700! hover:bg-brand-50!" icon="i-lucide-send" :to="localePath('/kirish')" :label="t('home.cta.primary')" />
          <UButton size="xl" variant="outline" color="neutral" class="justify-center bg-transparent! text-white! ring-white/60! hover:bg-white/10!" :to="localePath({ path: '/', hash: '#how' })" :label="t('home.cta.secondary')" />
        </div>
      </div>
    </div>
  </section>
</template>
