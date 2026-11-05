<script setup lang="ts">
interface Bucket { count: number, median: number, p25: number, p75: number, min: number, max: number }
interface PriceData {
  region: string | null
  sample_size: number
  window_days: number
  updated_at: string
  units: Record<'month' | 'day' | 'hour', { nanny: Bucket | null, vacancy: Bucket | null }>
  regions: { slug: string, count: number, median: number }[]
}

const UNITS = ['month', 'day', 'hour'] as const
const SIDES = ['nanny', 'vacancy'] as const
const ALL = 'all'

const { t } = useI18n()
const api = useApi()
const route = useRoute()
const router = useRouter()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { money, date } = useFormat()
const config = useRuntimeConfig()

const region = computed(() => (typeof route.query.region === 'string' ? route.query.region : ''))
const { data: regions } = await useRegions()
const { data, status } = await useAsyncData(
  'prices',
  () => api<{ data: PriceData }>('/prices', { query: { region: region.value || undefined } }).then(r => r.data).catch(() => null),
  { watch: [region] },
)

const year = new Date().getFullYear()
const regionName = computed(() => {
  const r = regions.value.find(x => x.slug === region.value)
  return r ? localizedName(r.name) : ''
})
const regionItems = computed(() => [{ label: t('search.allRegions'), value: ALL }, ...regions.value.map(r => ({ label: localizedName(r.name), value: r.slug }))])
const regionNameBySlug = (slug: string) => {
  const r = regions.value.find(x => x.slug === slug)
  return r ? localizedName(r.name) : slug
}
function setRegion(v: string) {
  router.push({ query: v && v !== ALL ? { region: v } : {} })
}

const hasData = computed(() => !!data.value && UNITS.some(u => SIDES.some(s => data.value!.units[u][s])))
const pending = computed(() => status.value === 'pending')

const title = computed(() => (regionName.value ? t('prices.seoTitleRegion', { year, region: regionName.value }) : t('prices.seoTitle', { year })))
const description = computed(() => (regionName.value ? t('prices.seoDescriptionRegion', { region: regionName.value }) : t('prices.seoDescription')))
const canonical = computed(() => config.public.siteUrl + localePath({ path: '/narxlar', query: region.value ? { region: region.value } : {} }))
useSeoMeta({ title, description, ogTitle: title, ogDescription: description, ogUrl: canonical })
useHead({ link: [{ rel: 'canonical', href: canonical }] })
</script>

<template>
  <section class="bg-linear-to-b from-brand-50 to-default py-12 dark:from-brand-950 dark:to-default sm:py-16">
    <UContainer class="max-w-4xl">
      <h1 class="h1-title text-highlighted">
        {{ regionName ? t('prices.titleRegion', { year, region: regionName }) : t('prices.title', { year }) }}
      </h1>
      <p class="mt-3 text-lg text-muted">{{ t('prices.lead') }}</p>

      <div class="mt-6 max-w-xs">
        <USelect :model-value="region || ALL" :items="regionItems" icon="i-lucide-map-pin" class="w-full" :aria-label="t('prices.regionLabel')" @update:model-value="v => setRegion(v as string)" />
      </div>

      <div v-if="pending" class="surface mt-8 h-64 animate-pulse" aria-hidden="true" />

      <template v-else-if="hasData && data">
        <div v-for="unit in UNITS" :key="unit" class="mt-8">
          <template v-if="data.units[unit].nanny || data.units[unit].vacancy">
            <h2 class="font-display text-xl font-extrabold text-highlighted">{{ t(`prices.unit.${unit}`) }}</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
              <template v-for="side in SIDES" :key="side">
                <div v-if="data.units[unit][side]" class="surface p-5">
                  <p class="text-sm font-semibold text-muted">{{ t(`prices.side.${side}`) }}</p>
                  <p class="mt-1 font-display text-3xl font-black tabular-nums text-highlighted">
                    {{ money(data.units[unit][side]!.median) }}
                    <span class="text-base font-bold text-muted">{{ t('format.som') }}/{{ t(`format.per.${unit}`) }}</span>
                  </p>
                  <p class="mt-1 text-sm text-muted">
                    {{ t('prices.typical', { from: money(data.units[unit][side]!.p25), to: money(data.units[unit][side]!.p75) }) }}
                  </p>
                  <p class="mt-3 text-xs text-muted">
                    {{ t('prices.count', { n: data.units[unit][side]!.count }) }} ·
                    {{ t('prices.minMax', { min: money(data.units[unit][side]!.min), max: money(data.units[unit][side]!.max) }) }}
                  </p>
                </div>
                <div v-else class="surface flex items-center p-5 text-sm text-muted">
                  <span>{{ t(`prices.side.${side}`) }}: {{ t('prices.notEnough') }}</span>
                </div>
              </template>
            </div>
          </template>
        </div>

        <section v-if="!region && data.regions.length" class="surface mt-10 p-6" aria-labelledby="prices-regions">
          <h2 id="prices-regions" class="font-display text-lg font-extrabold text-highlighted">{{ t('prices.tableTitle') }}</h2>
          <table class="mt-3 w-full text-left text-sm">
            <thead class="text-muted">
              <tr>
                <th scope="col" class="py-2 font-semibold">{{ t('prices.colRegion') }}</th>
                <th scope="col" class="py-2 text-right font-semibold">{{ t('prices.colMedian') }}</th>
                <th scope="col" class="py-2 text-right font-semibold">{{ t('prices.colCount') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-default">
              <tr v-for="r in data.regions" :key="r.slug">
                <td class="py-2.5">
                  <NuxtLink :to="localePath({ path: '/narxlar', query: { region: r.slug } })" class="font-semibold text-highlighted hover:text-brand-600 dark:hover:text-brand-300">{{ regionNameBySlug(r.slug) }}</NuxtLink>
                </td>
                <td class="py-2.5 text-right font-display font-black tabular-nums text-highlighted">{{ money(r.median) }} {{ t('format.som') }}</td>
                <td class="py-2.5 text-right tabular-nums text-muted">{{ r.count }}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <p class="mt-6 text-sm text-muted">
          {{ t('prices.note', { n: data.sample_size, days: data.window_days }) }}
          <span v-if="data.updated_at"> {{ t('prices.updated', { date: date(data.updated_at) }) }}</span>
        </p>
      </template>

      <div v-else class="surface mt-8 p-8 text-center">
        <UIcon name="i-lucide-bar-chart-3" class="mx-auto size-10 text-muted" aria-hidden="true" />
        <p class="mt-3 font-display text-lg font-extrabold text-highlighted">{{ t('prices.emptyTitle') }}</p>
        <p class="mt-1 text-muted">{{ t('prices.emptyText') }}</p>
        <UButton v-if="region" class="mt-4" color="neutral" variant="outline" :to="localePath('/narxlar')">{{ t('prices.allRegions') }}</UButton>
      </div>

      <div class="mt-10 flex flex-wrap gap-3">
        <UButton size="lg" :to="localePath({ path: '/enagalar', query: region ? { region } : {} })" trailing-icon="i-lucide-arrow-right">{{ t('prices.ctaNannies') }}</UButton>
        <UButton size="lg" color="neutral" variant="outline" :to="localePath({ path: '/vakansiyalar', query: region ? { region } : {} })" trailing-icon="i-lucide-arrow-right">{{ t('prices.ctaVacancies') }}</UButton>
      </div>
    </UContainer>
  </section>
</template>
