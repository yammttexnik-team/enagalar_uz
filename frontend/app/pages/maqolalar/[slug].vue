<script setup lang="ts">
import type { ArticleSummary } from './index.vue'

interface ArticleFull extends ArticleSummary { body: { uz: string, ru: string } }

const { t, locale } = useI18n()
const route = useRoute()
const api = useApi()
const localePath = useLocalePath()
const config = useRuntimeConfig()
const { date } = useFormat()

const notFound = ref(false)
const { data: article } = await useAsyncData(`article-${route.params.slug}`, async () => {
  try {
    return (await api<{ data: ArticleFull }>(`/articles/${route.params.slug}`)).data
  }
  catch (e: unknown) {
    if ((e as { statusCode?: number }).statusCode === 404) notFound.value = true
    return null
  }
})
if (notFound.value || (!article.value && import.meta.server)) throw createError({ statusCode: 404, statusMessage: t('articles.notFound'), fatal: true })

const pick = (v?: { uz: string, ru: string }) => (v ? (locale.value === 'ru' ? v.ru : v.uz) : '')
const heading = computed(() => pick(article.value?.title))
const html = computed(() => renderMarkdown(pick(article.value?.body)))

const description = computed(() => pick(article.value?.excerpt))
const canonical = computed(() => config.public.siteUrl + localePath(`/maqolalar/${route.params.slug}`))
useSeoMeta({
  title: heading,
  description,
  ogTitle: heading,
  ogDescription: description,
  ogUrl: canonical,
  ogType: 'article',
  articlePublishedTime: () => article.value?.published_at ?? undefined,
})
useHead({ link: [{ rel: 'canonical', href: canonical }] })
</script>

<template>
  <article v-if="article" class="bg-linear-to-b from-brand-50 to-default py-12 dark:from-brand-950 dark:to-default sm:py-16">
    <UContainer class="max-w-3xl">
      <UButton :to="localePath('/maqolalar')" color="neutral" variant="link" icon="i-lucide-arrow-left" :label="t('articles.back')" class="-ml-2.5" />
      <h1 class="h1-title mt-2 text-highlighted"><AppText :text="heading" /></h1>
      <p v-if="article.published_at" class="mt-2 text-sm text-muted">{{ date(article.published_at) }}</p>

      <!-- html: renderMarkdown() avval butun matnni escape qiladi, xom HTML o'tmaydi -->
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="article-body surface mt-6 p-6 sm:p-8" v-html="html" />

      <section class="surface mt-8 p-6 text-center">
        <h2 class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t('articles.ctaTitle')" /></h2>
        <p class="mt-2 text-muted"><AppText :text="t('articles.ctaText')" /></p>
        <div class="mt-4 flex flex-wrap justify-center gap-3">
          <UButton :to="localePath('/enagalar')" icon="i-lucide-search" :label="t('articles.ctaNannies')" />
          <UButton :to="localePath('/vakansiyalar')" color="neutral" variant="outline" icon="i-lucide-briefcase" :label="t('articles.ctaVacancies')" />
        </div>
      </section>
    </UContainer>
  </article>
</template>

<style scoped>
.article-body :deep(h2) { margin-top: 1.75rem; margin-bottom: .5rem; font-family: var(--font-display, inherit); font-size: 1.25rem; font-weight: 800; color: var(--ui-text-highlighted); }
.article-body :deep(h2:first-child) { margin-top: 0; }
.article-body :deep(h3), .article-body :deep(h4) { margin-top: 1.25rem; margin-bottom: .375rem; font-size: 1.05rem; font-weight: 700; color: var(--ui-text-highlighted); }
.article-body :deep(p) { margin-top: .75rem; line-height: 1.75; color: var(--ui-text-toned); }
.article-body :deep(ul), .article-body :deep(ol) { margin-top: .75rem; padding-left: 1.5rem; color: var(--ui-text-toned); }
.article-body :deep(ul) { list-style: disc; }
.article-body :deep(ol) { list-style: decimal; }
.article-body :deep(li) { margin-top: .375rem; line-height: 1.7; }
.article-body :deep(strong) { font-weight: 700; color: var(--ui-text-highlighted); }
.article-body :deep(a) { color: var(--ui-color-primary-600); text-decoration: underline; }
</style>
