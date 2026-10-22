<script setup lang="ts">
const { t } = useI18n()
const api = useApi()
const route = useRoute()
const router = useRouter()
const localePath = useLocalePath()
const auth = useAuthStore()
const { relativeTime } = useFormat()
const { start, loading: starting } = useStartChat()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.applications.seo'), robots: 'noindex' })

const isNanny = computed(() => !!auth.user?.roles.includes('nanny'))
const page = computed(() => Math.max(1, Number(route.query.page) || 1))
const { data, status, error, refresh } = await useAsyncData('my-applications', () =>
  (isNanny.value ? api<Paginated<Application>>('/my/applications', { query: { page: page.value } }) : Promise.resolve(null)), { watch: [page, isNanny] })
const items = computed(() => data.value?.data ?? [])
</script>

<template>
  <div>
    <DashboardPageHead :title="t('dashboard.applications.mineTitle')" :subtitle="t('dashboard.applications.mineSubtitle')" />
    <DashboardRoleGate role="nanny">
      <SearchSkeleton v-if="status === 'pending'" />
      <div v-else-if="error" class="surface px-6 py-10 text-center" role="alert">
        <p class="text-muted">{{ t('dashboard.common.error') }}</p>
        <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="refresh()" />
      </div>
      <div v-else-if="!items.length" class="surface flex flex-col items-center px-6 py-12 text-center">
        <span class="grid size-14 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-send" class="size-7" aria-hidden="true" /></span>
        <p class="mt-4 max-w-md text-muted">{{ t('dashboard.applications.mineEmpty') }}</p>
        <UButton class="mt-5" :label="t('home.hero.ctaJob')" :to="localePath('/vakansiyalar')" />
      </div>
      <template v-else>
        <div class="space-y-4">
          <div v-for="a in items" :key="a.id" class="surface overflow-hidden p-0!">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-default px-5 py-3">
              <DashboardStatusBadge kind="application" :status="a.status" />
              <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm text-muted">{{ t('dashboard.applications.sentAgo', { when: relativeTime(a.created_at) }) }}</span>
                <UButton size="sm" color="primary" variant="soft" icon="i-lucide-message-circle" :loading="starting" :label="t('chat.entry.write')" @click="start({ application_id: a.id })" />
              </div>
            </div>
            <p v-if="a.message" class="mx-5 mt-4 rounded-xl bg-sand-100 px-4 py-3 text-[15px] italic text-default dark:bg-sand-800">“{{ a.message }}”</p>
            <div v-if="a.vacancy" class="p-3"><VacancyCard :vacancy="a.vacancy" class="border-0! shadow-none!" /></div>
          </div>
        </div>
        <div v-if="data && data.meta.last_page > 1" class="mt-8 flex justify-center">
          <UPagination :page="page" :total="data.meta.total" :items-per-page="data.meta.per_page" :aria-label="t('search.pagination')" @update:page="p => router.push({ query: { page: p } })" />
        </div>
      </template>
    </DashboardRoleGate>
  </div>
</template>
