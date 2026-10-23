<script setup lang="ts">
import type { VacancyItem } from '~/components/vacancy/Card.vue'

interface Paginated<T> { data: T[], meta: { total: number, per_page: number, current_page: number, last_page: number } }

const FILTER_KEYS = ['region', 'district', 'work_format', 'language', 'salary_min', 'salary_unit', 'live_in', 'is_urgent', 'children_age', 'sort'] as const

const { t } = useI18n()
const api = useApi()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const config = useRuntimeConfig()
const { filters, page, activeCount, set, reset } = useSearchQuery(FILTER_KEYS)
const { data: regions } = await useRegions()

const { data, status, error, refresh } = await useAsyncData(
  'vacancies',
  () => api<Paginated<VacancyItem>>('/vacancies', { query: filters.value }),
  { watch: [filters] },
)

const pending = computed(() => status.value === 'pending')
const total = computed(() => data.value?.meta.total ?? 0)
const items = computed(() => data.value?.data ?? [])
const sortItems = computed(() => ['newest', 'salary_desc'].map(v => ({ label: t(`vacancy.sort.${v}`), value: v })))

// SEO: region boʻlsa sarlavha va tavsif aniqlashadi. Kanonik URL faqat region bilan.
const regionName = computed(() => {
  const r = regions.value.find(x => x.slug === filters.value.region)
  return r ? localizedName(r.name) : ''
})
const title = computed(() => (regionName.value ? t('vacancy.seoTitleRegion', { region: regionName.value }) : t('vacancy.seoTitle')))
const description = computed(() => (regionName.value ? t('vacancy.seoDescriptionRegion', { region: regionName.value }) : t('vacancy.seoDescription')))
const canonical = computed(() => config.public.siteUrl + localePath({ path: '/vakansiyalar', query: filters.value.region ? { region: filters.value.region } : {} }))
useSeoMeta({ title, description, ogTitle: title, ogDescription: description, ogUrl: canonical })
useHead({ link: [{ rel: 'canonical', href: canonical }] })

function changePage(p: number) {
  set({ page: p }, true)
  if (import.meta.client) document.getElementById('results')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
</script>

<template>
  <SearchShell
    :title="t('vacancy.title')"
    :subtitle="t('vacancy.subtitle')"
    :count-label="t('vacancy.count', { n: total })"
    :pending="pending"
    :sort-items="sortItems"
    :sort="filters.sort || 'newest'"
    :active-count="activeCount"
    :total="total"
    @update:sort="v => set({ sort: v === 'newest' ? null : v })"
    @reset="reset"
  >
    <template #filters>
      <VacancyFilters :filters="filters" :regions="regions" @change="set" />
    </template>

    <SearchSkeleton v-if="pending" />
    <div v-else-if="error" class="surface px-6 py-12 text-center" role="alert">
      <UIcon name="i-lucide-wifi-off" class="size-10 text-muted" aria-hidden="true" />
      <h2 class="h3-title mt-4 text-highlighted">{{ t('search.errorTitle') }}</h2>
      <p class="mt-2 text-muted">{{ t('search.errorText') }}</p>
      <UButton class="mt-6" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="refresh()" />
    </div>
    <SearchEmpty
      v-else-if="!items.length"
      :title="t('vacancy.empty.title')"
      :text="t('vacancy.empty.text')"
      :clear="t('vacancy.empty.clear')"
      :post="t('vacancy.empty.post')"
      post-to="/kabinet/anketa"
      @clear="reset"
    />
    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2">
        <VacancyCard v-for="v in items" :key="v.id" :vacancy="v" />
      </div>
      <div v-if="data && data.meta.last_page > 1" class="mt-8 flex justify-center">
        <UPagination
          :page="page"
          :total="data.meta.total"
          :items-per-page="data.meta.per_page"
          :sibling-count="1"
          :aria-label="t('search.pagination')"
          @update:page="changePage"
        />
      </div>
    </template>

    <!-- Telegram kanallardan olingan e'lonlar: mahalliy natija bo'lmasa ham ko'rinadi -->
    <ImportedSection type="vacancy" :filters="filters" />
  </SearchShell>
</template>
