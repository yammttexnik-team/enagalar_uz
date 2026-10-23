<script setup lang="ts">
import type { NannyItem } from '~/components/nanny/Card.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const api = useApi()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { salary, enumLabel, date, relativeTime } = useFormat()
const { toastError } = useApiError()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.vacancies.detailSeo'), robots: 'noindex' })

const id = route.params.id as string
const { data: vacancy, status: vStatus } = await useAsyncData(`my-vacancy-${id}`, () => fetchMyVacancy(api, id))

const tab = computed(() => (route.query.tab === 'matches' ? 'matches' : 'applications'))
const tabs = computed(() => [
  { value: 'applications', icon: 'i-lucide-inbox', label: t('dashboard.vacancies.tabApplications') },
  { value: 'matches', icon: 'i-lucide-sparkles', label: t('dashboard.vacancies.tabMatches') },
])
const setTab = (v: string) => router.replace({ query: v === 'matches' ? { tab: 'matches' } : {} })

// --- Javoblar ---
const apps = ref<Application[]>([])
const appsLoading = ref(false)
const appsError = ref(false)
const appsLoaded = ref(false)
async function loadApps() {
  appsLoading.value = true
  appsError.value = false
  try {
    apps.value = (await api<Paginated<Application>>(`/my/vacancies/${id}/applications`)).data
    appsLoaded.value = true
  }
  catch { appsError.value = true }
  finally { appsLoading.value = false }
}

// --- AI tanlovi ---
const matches = ref<MatchItem<NannyItem>[]>([])
const matchLoading = ref(false)
const matchInactive = ref(false)
const matchError = ref(false)
const matchLoaded = ref(false)
async function loadMatches(refresh = false) {
  if (vacancy.value && vacancy.value.status !== 'active') {
    matchInactive.value = true
    return
  }
  matchLoading.value = true
  matchError.value = false
  try {
    matches.value = (await api<{ data: MatchItem<NannyItem>[] }>(`/my/vacancies/${id}/matches`, { query: refresh ? { refresh: 1 } : {}, retry: 0 })).data
    matchInactive.value = false
    matchLoaded.value = true
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 409) matchInactive.value = true
    else {
      matchError.value = true
      if (refresh) toastError(e)
    }
  }
  finally { matchLoading.value = false }
}

watch(tab, (v) => {
  if (v === 'applications' && !appsLoaded.value) loadApps()
  if (v === 'matches' && !matchLoaded.value) loadMatches()
}, { immediate: true })

const place = computed(() => vacancy.value ? [vacancy.value.district, vacancy.value.region].filter(Boolean).map(p => localizedName(p!.name)).join(', ') : '')
const newCount = computed(() => apps.value.filter(a => a.status === 'sent').length)

async function afterDelete() {
  await navigateTo(localePath('/kabinet/vakansiyalar'))
}
</script>

<template>
  <div>
    <DashboardRoleGate role="parent">
      <template v-if="vacancy">
        <DashboardPageHead :title="vacancy.title" :back="{ to: localePath('/kabinet/vakansiyalar'), label: t('dashboard.vacancies.title') }">
          <template #actions>
            <UButton v-if="vacancy.status === 'active'" size="sm" color="neutral" variant="ghost" icon="i-lucide-external-link" :label="t('dashboard.vacancies.publicView')" :to="localePath(`/vakansiyalar/${vacancy.id}`)" />
            <DashboardVacancyActions :vacancy="vacancy" @updated="v => (vacancy = v)" @deleted="afterDelete" />
          </template>
        </DashboardPageHead>

        <section class="surface mb-6 p-5 sm:p-6">
          <div class="flex flex-wrap items-center gap-2">
            <DashboardStatusBadge kind="vacancy" :status="vacancy.status" />
            <UBadge v-if="vacancy.is_urgent" color="secondary" variant="subtle" icon="i-lucide-zap" :label="t('vacancy.urgent')" />
            <PromoTopChip v-if="vacancy.top_until && new Date(vacancy.top_until).getTime() > Date.now()" />
            <span class="text-sm text-muted">{{ t('dashboard.vacancies.views', { n: vacancy.views_count }) }}</span>
          </div>
          <p v-if="vacancy.status === 'pending'" class="mt-3 flex items-start gap-2 rounded-xl bg-honey-50 px-3 py-2 text-sm text-honey-800 dark:bg-honey-950 dark:text-honey-200"><UIcon name="i-lucide-hourglass" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ t('dashboard.vacancies.pendingNote') }}</p>
          <p v-else-if="vacancy.status === 'rejected'" class="mt-3 flex items-start gap-2 rounded-xl bg-pomegranate-50 px-3 py-2 text-sm text-pomegranate-700 dark:bg-pomegranate-950 dark:text-pomegranate-300"><UIcon name="i-lucide-circle-alert" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ t('dashboard.vacancies.rejectedNote') }}</p>
          <dl class="mt-4 grid gap-x-8 gap-y-3 text-[15px] sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="eyebrow text-muted">{{ t('vacancy.detail.location') }}</dt><dd class="mt-0.5 font-semibold text-highlighted">{{ place }}</dd></div>
            <div><dt class="eyebrow text-muted">{{ t('vacancy.detail.format') }}</dt><dd class="mt-0.5 font-semibold text-highlighted">{{ enumLabel('workFormat', vacancy.work_format) }}</dd></div>
            <div><dt class="eyebrow text-muted">{{ t('vacancy.detail.salary') }}</dt><dd class="mt-0.5 font-semibold text-highlighted"><AppText :text="salary(vacancy.salary)" /></dd></div>
            <div><dt class="eyebrow text-muted">{{ vacancy.expires_at ? t('vacancy.detail.expires') : t('vacancy.detail.published') }}</dt><dd class="mt-0.5 font-semibold text-highlighted">{{ vacancy.expires_at ? date(vacancy.expires_at) : relativeTime(vacancy.published_at) || '—' }}</dd></div>
          </dl>
        </section>

        <!-- Ko'proq ko'rinish: TOP / Shoshilinch (bonus bilan) -->
        <PromoCard
          v-if="['active', 'pending'].includes(vacancy.status)"
          class="mb-6"
          target="vacancy"
          :id="vacancy.id"
          :top-until="vacancy.top_until"
          :urgent-until="vacancy.urgent_until"
          :active="vacancy.status === 'active'"
          @updated="u => vacancy && Object.assign(vacancy, u)"
        />

        <div role="tablist" :aria-label="vacancy.title" class="mb-5 inline-flex rounded-2xl bg-elevated p-1">
          <button
            v-for="x in tabs"
            :key="x.value"
            role="tab"
            type="button"
            :aria-selected="tab === x.value"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-primary"
            :class="tab === x.value ? 'bg-default text-highlighted shadow-sm' : 'text-muted hover:text-highlighted'"
            @click="setTab(x.value)"
          >
            <UIcon :name="x.icon" class="size-4" aria-hidden="true" />{{ x.label }}
            <span v-if="x.value === 'applications' && appsLoaded && apps.length" class="rounded-full bg-brand-500 px-1.5 text-xs text-white">{{ apps.length }}</span>
          </button>
        </div>

        <!-- Javoblar -->
        <section v-if="tab === 'applications'" role="tabpanel" :aria-label="t('dashboard.vacancies.tabApplications')">
          <SearchSkeleton v-if="appsLoading" />
          <div v-else-if="appsError" class="surface px-6 py-10 text-center" role="alert">
            <p class="text-muted">{{ t('dashboard.common.error') }}</p>
            <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="loadApps()" />
          </div>
          <div v-else-if="!apps.length" class="surface flex flex-col items-center px-6 py-12 text-center">
            <span class="grid size-14 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-inbox" class="size-7" aria-hidden="true" /></span>
            <p class="mt-4 max-w-md text-muted">{{ t('dashboard.applications.empty') }}</p>
          </div>
          <div v-else class="space-y-4">
            <p v-if="newCount" class="text-sm font-semibold text-primary">{{ t('dashboard.applications.newCount', { n: newCount }) }}</p>
            <DashboardApplicationItem v-for="a in apps" :key="a.id" :application="a" @status="s => (a.status = s)" />
          </div>
        </section>

        <!-- AI tanlovi -->
        <section v-else role="tabpanel" :aria-label="t('dashboard.vacancies.tabMatches')">
          <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="max-w-xl text-sm text-muted">{{ t('dashboard.match.hintVacancy') }}</p>
            <UButton v-if="!matchInactive" color="neutral" variant="outline" icon="i-lucide-refresh-cw" :loading="matchLoading" :label="t('dashboard.match.refresh')" @click="loadMatches(true)" />
          </div>
          <div v-if="matchInactive" class="surface flex flex-col items-start gap-3 border-honey-200! bg-honey-50! p-6 dark:border-honey-800! dark:bg-honey-950/40!" role="status">
            <span class="grid size-12 place-items-center rounded-2xl bg-honey-100 text-honey-800 dark:bg-honey-900 dark:text-honey-200"><UIcon name="i-lucide-hourglass" class="size-6" aria-hidden="true" /></span>
            <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('dashboard.match.inactiveTitle') }}</h2>
            <p class="max-w-xl text-muted">{{ t('dashboard.match.inactiveVacancy') }}</p>
          </div>
          <template v-else>
            <div v-if="matchLoading && !matches.length" class="surface flex items-center gap-3 px-6 py-10 text-muted" role="status">
              <UIcon name="i-lucide-loader-circle" class="size-5 animate-spin text-primary" aria-hidden="true" />{{ t('dashboard.match.loading') }}
            </div>
            <div v-else-if="matchError" class="surface px-6 py-10 text-center" role="alert">
              <p class="text-muted">{{ t('dashboard.common.error') }}</p>
              <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="loadMatches()" />
            </div>
            <div v-else-if="!matches.length" class="surface px-6 py-12 text-center text-muted">{{ t('dashboard.match.emptyVacancy') }}</div>
            <div v-else class="grid gap-4 xl:grid-cols-2" :class="matchLoading && 'opacity-60'">
              <DashboardMatchCard v-for="(m, i) in matches" :key="m.nanny?.id ?? i" kind="nanny" :match="m" />
            </div>
          </template>
        </section>
      </template>
      <div v-else-if="vStatus !== 'pending'" class="surface px-6 py-12 text-center">
        <p class="text-muted">{{ t('vacancy.detail.notFound') }}</p>
        <UButton class="mt-5" :label="t('dashboard.vacancies.title')" :to="localePath('/kabinet/vakansiyalar')" />
      </div>
    </DashboardRoleGate>
  </div>
</template>
