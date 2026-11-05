<script setup lang="ts">
export interface ArticleSummary {
  slug: string
  title: { uz: string, ru: string }
  excerpt: { uz: string, ru: string }
  published_at: string | null
}

const { t, locale } = useI18n()
const api = useApi()
const route = useRoute()
const localePath = useLocalePath()
const config = useRuntimeConfig()
const { date } = useFormat()

const page = computed(() => Math.max(1, Number(route.query.page) || 1))
const { data, status } = await useAsyncData(
  'articles',
  () => api<{ data: ArticleSummary[], meta: { last_page: number } }>('/articles', { query: { page: page.value } }).catch(() => null),
  { watch: [page] },
)
const items = computed(() => data.value?.data ?? [])
const lastPage = computed(() => data.value?.meta.last_page ?? 1)
const pick = (v: { uz: string, ru: string }) => (locale.value === 'ru' ? v.ru : v.uz)

const title = computed(() => t('articles.seoTitle'))
const description = computed(() => t('articles.seoDescription'))
const canonical = computed(() => config.public.siteUrl + localePath({ path: '/maqolalar', query: page.value > 1 ? { page: String(page.value) } : {} }))
useSeoMeta({ title, description, ogTitle: title, ogDescription: description, ogUrl: canonical })
useHead({ link: [{ rel: 'canonical', href: canonical }] })
</script>

<template>
  <section class="bg-linear-to-b from-brand-50 to-default py-12 dark:from-brand-950 dark:to-default sm:py-16">
    <UContainer class="max-w-4xl">
      <h1 class="h1-title text-highlighted"><AppText :text="t('articles.title')" /></h1>
      <p class="mt-3 text-lg text-muted"><AppText :text="t('articles.lead')" /></p>

      <p v-if="status !== 'pending' && !data" class="surface mt-8 p-6 text-muted">{{ t('articles.loadFailed') }}</p>
      <p v-else-if="status !== 'pending' && !items.length" class="surface mt-8 p-6 text-muted">{{ t('articles.empty') }}</p>

      <ul v-else class="mt-8 grid gap-4 sm:grid-cols-2">
        <li v-for="a in items" :key="a.slug">
          <NuxtLink :to="localePath(`/maqolalar/${a.slug}`)" class="surface flex h-full flex-col p-5 transition hover:shadow-md">
            <p v-if="a.published_at" class="text-xs font-semibold text-muted">{{ date(a.published_at) }}</p>
            <h2 class="mt-1 font-display text-lg font-extrabold text-highlighted"><AppText :text="pick(a.title)" /></h2>
            <p class="mt-2 line-clamp-3 text-sm text-muted"><AppText :text="pick(a.excerpt)" /></p>
            <span class="mt-auto pt-4 text-sm font-bold text-brand-700 dark:text-brand-300">
              {{ t('articles.read') }} <UIcon name="i-lucide-arrow-right" class="size-4 align-text-bottom" aria-hidden="true" />
            </span>
          </NuxtLink>
        </li>
      </ul>

      <nav v-if="lastPage > 1" class="mt-8 flex items-center justify-between" :aria-label="t('articles.pages')">
        <UButton v-if="page > 1" :to="localePath({ path: '/maqolalar', query: page > 2 ? { page: String(page - 1) } : {} })" color="neutral" variant="outline" icon="i-lucide-arrow-left" :label="t('articles.prev')" />
        <span v-else />
        <UButton v-if="page < lastPage" :to="localePath({ path: '/maqolalar', query: { page: String(page + 1) } })" color="neutral" variant="outline" trailing-icon="i-lucide-arrow-right" :label="t('articles.next')" />
      </nav>
    </UContainer>
  </section>
</template>
