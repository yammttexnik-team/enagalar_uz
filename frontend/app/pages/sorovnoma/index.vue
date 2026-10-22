<script setup lang="ts">
import type { SurveyListItem } from '~/composables/useBonus'

const { t } = useI18n()
const localePath = useLocalePath()
const auth = useAuthStore()
const api = useApi()
const { money } = useFormat()
const { locale } = useI18n()

// Kirmagan bo'lsa /kirish?redirect=/sorovnoma?c=KOD (kampaniya cookie'da saqlanadi).
definePageMeta({ middleware: 'auth' })
useSeoMeta({ title: () => t('survey.seo'), robots: 'noindex' })

const { data: surveys, error, refresh } = await useAsyncData('survey-list', () => api<{ data: SurveyListItem[] }>('/surveys', { query: { lang: locale.value } }).then(r => r.data), { default: () => [] as SurveyListItem[] })

// Roli aniq bo'lsa (faqat enaga yoki faqat ota-ona) — to'g'ridan-to'g'ri o'z so'rovnomasiga.
const roles = auth.user?.roles ?? []
const single = roles.length === 1 ? surveys.value.find(s => s.audience === roles[0]) : undefined
if (single) await navigateTo(localePath(`/sorovnoma/${single.slug}`), { replace: true })

const options = computed(() => surveys.value.map(s => ({ ...s, icon: s.audience === 'nanny' ? 'i-lucide-heart-handshake' : 'i-lucide-house-heart' })))
</script>

<template>
  <section class="bg-linear-to-b from-brand-50 to-default py-12 dark:from-brand-950 dark:to-default sm:py-20">
    <UContainer class="max-w-3xl">
      <div class="text-center">
        <h1 class="h1-title text-highlighted">{{ t('survey.landingTitle') }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-lg text-muted">{{ t('survey.landingText') }}</p>
      </div>
      <p v-if="error" class="mt-8 text-center text-muted" role="alert">
        {{ t('survey.loadError') }} <UButton variant="link" :label="t('survey.retry')" @click="refresh()" />
      </p>
      <h2 class="mt-10 text-center font-display text-xl font-extrabold text-highlighted">{{ t('survey.chooseTitle') }}</h2>
      <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <NuxtLink v-for="s in options" :key="s.slug" :to="localePath(`/sorovnoma/${s.slug}`)" class="surface flex flex-col items-center gap-3 p-6 text-center transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)] focus-visible:outline-2 focus-visible:outline-primary">
          <span class="grid size-14 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon :name="s.icon" class="size-7" aria-hidden="true" /></span>
          <span class="font-display text-lg font-extrabold text-highlighted">{{ t(`survey.audience.${s.audience}`) }}</span>
          <UBadge v-if="s.completed" color="success" variant="subtle" icon="i-lucide-check" :label="t('survey.done')" />
          <span v-else class="text-sm font-bold text-apricot-700 dark:text-apricot-300">{{ t('survey.bonus', { n: money(s.bonus) }) }}</span>
        </NuxtLink>
      </div>
    </UContainer>
  </section>
</template>
