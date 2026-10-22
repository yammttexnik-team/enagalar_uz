<script setup lang="ts">
const { t } = useI18n()
const api = useApi()
const route = useRoute()
const router = useRouter()
const localePath = useLocalePath()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.vacancies.seo'), robots: 'noindex' })

const page = computed(() => Math.max(1, Number(route.query.page) || 1))
const auth = useAuthStore()
const isParent = computed(() => !!auth.user?.roles.includes('parent'))

const { data, status, error, refresh } = await useAsyncData(
  'my-vacancies',
  () => (isParent.value ? api<Paginated<OwnVacancy>>('/my/vacancies', { query: { page: page.value } }) : Promise.resolve(null)),
  { watch: [page, isParent] },
)
const items = computed(() => data.value?.data ?? [])
const loading = computed(() => status.value === 'pending')

function patch(v: OwnVacancy) {
  if (data.value) data.value = { ...data.value, data: data.value.data.map(x => (x.id === v.id ? v : x)) }
}
</script>

<template>
  <div>
    <DashboardPageHead :title="t('dashboard.vacancies.title')" :subtitle="t('dashboard.vacancies.subtitle')">
      <template v-if="isParent" #actions>
        <UButton size="lg" icon="i-lucide-plus" :label="t('dashboard.vacancies.new')" :to="localePath('/kabinet/vakansiyalar/yangi')" />
      </template>
    </DashboardPageHead>

    <DashboardRoleGate role="parent">
      <SearchSkeleton v-if="loading" />
      <div v-else-if="error" class="surface px-6 py-12 text-center" role="alert">
        <UIcon name="i-lucide-wifi-off" class="size-10 text-muted" aria-hidden="true" />
        <p class="mt-3 text-muted">{{ t('dashboard.common.error') }}</p>
        <UButton class="mt-5" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="refresh()" />
      </div>
      <div v-else-if="!items.length" class="surface flex flex-col items-center px-6 py-14 text-center">
        <span class="grid size-16 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-megaphone" class="size-8" aria-hidden="true" /></span>
        <h2 class="h3-title mt-5 text-highlighted">{{ t('dashboard.vacancies.emptyTitle') }}</h2>
        <p class="mt-2 max-w-md text-muted">{{ t('dashboard.vacancies.emptyText') }}</p>
        <UButton class="mt-6" size="lg" icon="i-lucide-plus" :label="t('dashboard.vacancies.new')" :to="localePath('/kabinet/vakansiyalar/yangi')" />
      </div>
      <template v-else>
        <div class="space-y-4">
          <DashboardVacancyRow v-for="v in items" :key="v.id" :vacancy="v" @updated="patch" @deleted="refresh()" />
        </div>
        <div v-if="data && data.meta.last_page > 1" class="mt-8 flex justify-center">
          <UPagination :page="page" :total="data.meta.total" :items-per-page="data.meta.per_page" :aria-label="t('search.pagination')" @update:page="p => router.push({ query: { page: p } })" />
        </div>
      </template>
    </DashboardRoleGate>
  </div>
</template>
