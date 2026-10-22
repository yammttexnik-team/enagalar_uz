<script setup lang="ts">
import type { NannyItem } from '~/components/nanny/Card.vue'
import type { VacancyItem } from '~/components/vacancy/Card.vue'

const { t } = useI18n()
const api = useApi()
const route = useRoute()
const router = useRouter()
const localePath = useLocalePath()
const fav = useFavorites()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.favorites.seo'), robots: 'noindex' })

interface Fav { type: 'nanny' | 'vacancy', item: NannyItem & VacancyItem }
await fav.load()
const { data, status, error, refresh } = await useAsyncData('my-favorites', () => api<{ data: Fav[] }>('/favorites').then(r => r.data), { default: () => [] as Fav[] })

const tab = computed(() => (route.query.tab === 'vacancies' ? 'vacancies' : 'nannies'))
const nannies = computed(() => data.value.filter(f => f.type === 'nanny' && fav.has('nanny', f.item.id)))
const vacancies = computed(() => data.value.filter(f => f.type === 'vacancy' && fav.has('vacancy', f.item.id)))
const tabs = computed(() => [
  { value: 'nannies', label: t('dashboard.favorites.nannies'), n: nannies.value.length },
  { value: 'vacancies', label: t('dashboard.favorites.vacancies'), n: vacancies.value.length },
])
</script>

<template>
  <div>
    <DashboardPageHead :title="t('dashboard.favorites.title')" :subtitle="t('dashboard.favorites.subtitle')" />

    <div role="tablist" :aria-label="t('dashboard.favorites.title')" class="mb-5 inline-flex rounded-2xl bg-elevated p-1">
      <button
        v-for="x in tabs"
        :key="x.value"
        role="tab"
        type="button"
        :aria-selected="tab === x.value"
        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-primary"
        :class="tab === x.value ? 'bg-default text-highlighted shadow-sm' : 'text-muted hover:text-highlighted'"
        @click="router.replace({ query: x.value === 'vacancies' ? { tab: 'vacancies' } : {} })"
      >
        {{ x.label }}<span class="rounded-full bg-sand-200 px-1.5 text-xs dark:bg-sand-700">{{ x.n }}</span>
      </button>
    </div>

    <SearchSkeleton v-if="status === 'pending'" />
    <div v-else-if="error" class="surface px-6 py-10 text-center" role="alert">
      <p class="text-muted">{{ t('dashboard.common.error') }}</p>
      <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="refresh()" />
    </div>
    <template v-else>
      <div v-if="tab === 'nannies'">
        <div v-if="nannies.length" class="grid gap-4 sm:grid-cols-2">
          <NannyCard v-for="f in nannies" :key="f.item.id" :nanny="f.item" />
        </div>
        <div v-else class="surface flex flex-col items-center px-6 py-12 text-center">
          <span class="grid size-14 place-items-center rounded-full bg-pomegranate-100 text-pomegranate-600 dark:bg-pomegranate-900 dark:text-pomegranate-300"><UIcon name="i-lucide-heart" class="size-7" aria-hidden="true" /></span>
          <p class="mt-4 max-w-md text-muted">{{ t('dashboard.favorites.emptyNannies') }}</p>
          <UButton class="mt-5" :label="t('home.hero.ctaNanny')" :to="localePath('/enagalar')" />
        </div>
      </div>
      <div v-else>
        <div v-if="vacancies.length" class="grid gap-4 sm:grid-cols-2">
          <VacancyCard v-for="f in vacancies" :key="f.item.id" :vacancy="f.item" />
        </div>
        <div v-else class="surface flex flex-col items-center px-6 py-12 text-center">
          <span class="grid size-14 place-items-center rounded-full bg-pomegranate-100 text-pomegranate-600 dark:bg-pomegranate-900 dark:text-pomegranate-300"><UIcon name="i-lucide-heart" class="size-7" aria-hidden="true" /></span>
          <p class="mt-4 max-w-md text-muted">{{ t('dashboard.favorites.emptyVacancies') }}</p>
          <UButton class="mt-5" :label="t('home.hero.ctaJob')" :to="localePath('/vakansiyalar')" />
        </div>
      </div>
    </template>
  </div>
</template>
