<script setup lang="ts">
import type { NuxtError } from '#app'

const props = defineProps<{ error: NuxtError }>()
const { t } = useI18n()
const localePath = useLocalePath()
const notFound = computed(() => props.error.statusCode === 404)
useSeoMeta({ title: () => (notFound.value ? t('errorPage.notFoundTitle') : t('errorPage.genericTitle')), robots: 'noindex' })
</script>

<template>
  <UApp>
    <NuxtLayout>
      <section class="py-20 sm:py-28">
        <UContainer class="max-w-lg text-center">
          <p class="font-display text-7xl font-black text-brand-200 dark:text-brand-800">{{ error.statusCode }}</p>
          <h1 class="h1-title mt-4 text-highlighted">{{ notFound ? t('errorPage.notFoundTitle') : t('errorPage.genericTitle') }}</h1>
          <p class="mt-3 text-lg text-muted">{{ notFound ? t('errorPage.notFoundText') : t('errorPage.genericText') }}</p>
          <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <UButton size="xl" class="justify-center" icon="i-lucide-search" :to="localePath('/enagalar')" :label="t('errorPage.nannies')" @click="clearError()" />
            <UButton size="xl" class="justify-center" color="neutral" variant="outline" :to="localePath('/')" :label="t('errorPage.home')" @click="clearError()" />
          </div>
        </UContainer>
      </section>
    </NuxtLayout>
  </UApp>
</template>
