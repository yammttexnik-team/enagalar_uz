<script setup lang="ts">
import type { NannyItem } from '~/components/nanny/Card.vue'

interface Paginated<T> { data: T[], meta: { total: number, per_page: number, current_page: number, last_page: number } }

const FILTER_KEYS = ['region', 'district', 'work_format', 'language', 'age_group', 'skill', 'price_max', 'price_unit', 'experience_min', 'live_in', 'available_only', 'verified_only', 'badge', 'sort'] as const

const { t } = useI18n()
const api = useApi()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const config = useRuntimeConfig()
const { filters, page, activeCount, set, reset } = useSearchQuery(FILTER_KEYS)
const { data: regions } = await useRegions()
const { data: skills } = await useSkills()

const { data, status, error, refresh } = await useAsyncData(
  'nannies',
  () => api<Paginated<NannyItem>>('/nannies', { query: filters.value }),
  { watch: [filters] },
)

// Telegram kanallardan olingan e'lonlar soni (shu filtrlar bilan): bo'sh holat ixcham ko'rinadi, sanoqda ham chiqadi
const importedQuery = computed(() => ({ type: 'nanny', region: filters.value.region || undefined, district: filters.value.district || undefined, work_format: filters.value.work_format || undefined }))
const { data: importedTotal } = await useAsyncData(
  'imported-count-nanny',
  () => api<{ meta: { total: number } }>('/imported', { query: importedQuery.value }).then(r => r.meta.total).catch(() => 0),
  { watch: [importedQuery] },
)

const pending = computed(() => status.value === 'pending')
const total = computed(() => data.value?.meta.total ?? 0)
const nannies = computed(() => data.value?.data ?? [])
const sortItems = computed(() => ['newest', 'rating', 'price_asc', 'experience'].map(v => ({ label: t(`nanny.sort.${v}`), value: v })))

// SEO: region boʻlsa sarlavha va tavsif aniqlashadi. Kanonik URL faqat region bilan.
const regionName = computed(() => {
  const r = regions.value.find(x => x.slug === filters.value.region)
  return r ? localizedName(r.name) : ''
})
const title = computed(() => (regionName.value ? t('nanny.seoTitleRegion', { region: regionName.value }) : t('nanny.seoTitle')))
const description = computed(() => (regionName.value ? t('nanny.seoDescriptionRegion', { region: regionName.value }) : t('nanny.seoDescription')))
const canonical = computed(() => config.public.siteUrl + localePath({ path: '/enagalar', query: filters.value.region ? { region: filters.value.region } : {} }))
useSeoMeta({ title, description, ogTitle: title, ogDescription: description, ogUrl: canonical })
useHead({ link: [{ rel: 'canonical', href: canonical }] })

function changePage(p: number) {
  set({ page: p }, true)
  if (import.meta.client) document.getElementById('results')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
</script>

<template>
  <SearchShell
    :title="t('nanny.title')"
    :subtitle="t('nanny.subtitle')"
    :count-label="importedTotal ? t('nanny.count', { n: total }) + ' · ' + t('imported.section.count', { n: importedTotal }) : t('nanny.count', { n: total })"
    :pending="pending"
    :sort-items="sortItems"
    :sort="filters.sort || 'newest'"
    :active-count="activeCount"
    :total="total"
    @update:sort="v => set({ sort: v === 'newest' ? null : v })"
    @reset="reset"
  >
    <template #filters>
      <NannyFilters :filters="filters" :regions="regions" :skills="skills" @change="set" />
    </template>

    <SearchSkeleton v-if="pending" />
    <div v-else-if="error" class="surface px-6 py-12 text-center" role="alert">
      <UIcon name="i-lucide-wifi-off" class="size-10 text-muted" aria-hidden="true" />
      <h2 class="h3-title mt-4 text-highlighted">{{ t('search.errorTitle') }}</h2>
      <p class="mt-2 text-muted">{{ t('search.errorText') }}</p>
      <UButton class="mt-6" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="refresh()" />
    </div>
    <SearchEmpty
      v-else-if="!nannies.length"
      :compact="!!importedTotal"
      :title="t('nanny.empty.title')"
      :text="t('nanny.empty.text')"
      :clear="t('nanny.empty.clear')"
      :post="t('nanny.empty.post')"
      post-to="/kabinet/vakansiyalar/yangi"
      @clear="reset"
    />
    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2">
        <NannyCard v-for="n in nannies" :key="n.id" :nanny="n" />
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
    <ImportedSection type="nanny" :filters="filters" />
  </SearchShell>
</template>
