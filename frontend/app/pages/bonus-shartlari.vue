<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()

useSeoMeta({
  title: () => t('terms.seo.title'),
  description: () => t('terms.seo.description'),
  ogTitle: () => t('terms.seo.title'),
  ogDescription: () => t('terms.seo.description'),
})

// Qanday olinadi: harakat, summa va izoh.
const earn = ['survey', 'listing', 'referral', 'verification'] as const
const spend = ['top', 'urgent'] as const
const what = ['rate', 'noCash', 'validity', 'cap'] as const
const revoke = ['fake', 'deleted'] as const
</script>

<template>
  <section class="bg-linear-to-b from-brand-50 to-default py-12 dark:from-brand-950 dark:to-default sm:py-20">
    <UContainer class="max-w-3xl">
      <h1 class="h1-title text-highlighted"><AppText :text="t('terms.title')" /></h1>
      <p class="mt-3 text-lg text-muted">{{ t('terms.lead') }}</p>

      <section class="surface mt-8 p-6" aria-labelledby="terms-what">
        <h2 id="terms-what" class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t('terms.whatTitle')" /></h2>
        <ul class="mt-3 space-y-2 text-muted">
          <li v-for="k in what" :key="k" class="flex gap-2">
            <UIcon name="i-lucide-check" class="mt-1 size-4 shrink-0 text-leaf-600" aria-hidden="true" />
            <span><AppText :text="t(`terms.what.${k}`)" /></span>
          </li>
        </ul>
      </section>

      <section class="surface mt-6 p-6" aria-labelledby="terms-earn">
        <h2 id="terms-earn" class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t('terms.earnTitle')" /></h2>
        <ul class="mt-3 divide-y divide-default">
          <li v-for="k in earn" :key="k" class="flex items-start justify-between gap-4 py-3">
            <div class="min-w-0">
              <p class="font-semibold text-highlighted"><AppText :text="t(`terms.earn.${k}.title`)" /></p>
              <p class="text-sm text-muted"><AppText :text="t(`terms.earn.${k}.text`)" /></p>
            </div>
            <p class="shrink-0 text-right font-display font-black tabular-nums text-apricot-700 dark:text-apricot-300">
              {{ t(`terms.earn.${k}.amount`) }} <span class="text-sm font-bold text-muted"><AppText :text="t('terms.earn.som')" /></span>
            </p>
          </li>
        </ul>
      </section>

      <section class="surface mt-6 p-6" aria-labelledby="terms-spend">
        <h2 id="terms-spend" class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t('terms.spendTitle')" /></h2>
        <ul class="mt-3 divide-y divide-default">
          <li v-for="k in spend" :key="k" class="flex items-center justify-between gap-4 py-3">
            <p class="font-semibold text-highlighted"><AppText :text="t(`terms.spend.${k}.title`)" /></p>
            <p class="shrink-0 font-display font-black tabular-nums text-highlighted">
              {{ t(`terms.spend.${k}.amount`) }} <span class="text-sm font-bold text-muted"><AppText :text="t('terms.spend.som')" /></span>
            </p>
          </li>
        </ul>
        <p class="mt-3 text-sm text-muted"><AppText :text="t('terms.spend.more')" /></p>
      </section>

      <section class="surface mt-6 p-6" aria-labelledby="terms-revoke">
        <h2 id="terms-revoke" class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t('terms.revokeTitle')" /></h2>
        <ul class="mt-3 space-y-2 text-muted">
          <li v-for="k in revoke" :key="k" class="flex gap-2">
            <UIcon name="i-lucide-circle-x" class="mt-1 size-4 shrink-0 text-pomegranate-600" aria-hidden="true" />
            <span><AppText :text="t(`terms.revoke.${k}`)" /></span>
          </li>
        </ul>
      </section>

      <section class="surface mt-6 p-6" aria-labelledby="terms-change">
        <h2 id="terms-change" class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t('terms.changeTitle')" /></h2>
        <p class="mt-3 text-muted"><AppText :text="t('terms.change')" /></p>
      </section>

      <div class="mt-8 text-center">
        <UButton :to="localePath('/kabinet/bonuslar')" icon="i-lucide-gift" :label="t('terms.cta')" />
      </div>
    </UContainer>
  </section>
</template>
