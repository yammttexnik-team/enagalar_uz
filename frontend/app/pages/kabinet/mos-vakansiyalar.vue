<script setup lang="ts">
import type { VacancyItem } from '~/components/vacancy/Card.vue'

const { t } = useI18n()
const api = useApi()
const localePath = useLocalePath()
const { toastError } = useApiError()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.matches.seo'), robots: 'noindex' })

const items = ref<MatchItem<VacancyItem>[]>([])
const loading = ref(true)
const inactive = ref(false)
const failed = ref(false)

let started = false
async function load(refresh = false) {
  if (started && !refresh) return
  started = true
  loading.value = true
  failed.value = false
  try {
    items.value = (await api<{ data: MatchItem<VacancyItem>[] }>('/my/nanny-profile/matches', { query: refresh ? { refresh: 1 } : {}, retry: 0 })).data
    inactive.value = false
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 409 || p.status === 403) inactive.value = true
    else {
      failed.value = true
      started = false
      if (refresh) toastError(e)
    }
  }
  finally { loading.value = false }
}
const auth = useAuthStore()
const isNanny = computed(() => !!auth.user?.roles.includes('nanny'))
watch(isNanny, (v) => { if (v) load() })
onMounted(() => { if (isNanny.value) load() })
</script>

<template>
  <div>
    <DashboardPageHead :title="t('dashboard.matches.title')" :subtitle="t('dashboard.matches.subtitle')">
      <template v-if="isNanny && !inactive" #actions>
        <UButton color="neutral" variant="outline" icon="i-lucide-refresh-cw" :loading="loading" :label="t('dashboard.match.refresh')" @click="load(true)" />
      </template>
    </DashboardPageHead>

    <DashboardRoleGate role="nanny">
      <div v-if="inactive" class="surface flex flex-col items-start gap-3 border-honey-200! bg-honey-50! p-6 dark:border-honey-800! dark:bg-honey-950/40!" role="status">
        <span class="grid size-12 place-items-center rounded-2xl bg-honey-100 text-honey-800 dark:bg-honey-900 dark:text-honey-200"><UIcon name="i-lucide-hourglass" class="size-6" aria-hidden="true" /></span>
        <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('dashboard.match.inactiveTitle') }}</h2>
        <p class="max-w-xl text-muted">{{ t('dashboard.match.inactiveNanny') }}</p>
        <UButton icon="i-lucide-file-pen-line" :label="t('dashboard.nav.profile')" :to="localePath('/kabinet/anketa')" />
      </div>
      <template v-else>
        <div v-if="loading && !items.length" class="surface flex items-center gap-3 px-6 py-10 text-muted" role="status">
          <UIcon name="i-lucide-loader-circle" class="size-5 animate-spin text-primary" aria-hidden="true" />{{ t('dashboard.match.loading') }}
        </div>
        <div v-else-if="failed" class="surface px-6 py-10 text-center" role="alert">
          <p class="text-muted">{{ t('dashboard.common.error') }}</p>
          <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="load()" />
        </div>
        <div v-else-if="!items.length" class="surface flex flex-col items-center px-6 py-12 text-center">
          <span class="grid size-14 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-sparkles" class="size-7" aria-hidden="true" /></span>
          <p class="mt-4 max-w-md text-muted">{{ t('dashboard.match.emptyNanny') }}</p>
          <UButton class="mt-5" color="neutral" variant="outline" :label="t('home.hero.ctaJob')" :to="localePath('/vakansiyalar')" />
        </div>
        <div v-else class="grid gap-4 xl:grid-cols-2" :class="loading && 'opacity-60'">
          <DashboardMatchCard v-for="(m, i) in items" :key="m.vacancy?.id ?? i" kind="vacancy" :match="m" />
        </div>
      </template>
    </DashboardRoleGate>
  </div>
</template>
