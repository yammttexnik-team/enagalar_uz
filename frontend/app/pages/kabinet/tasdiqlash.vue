<script setup lang="ts">
const { t } = useI18n()
const api = useApi()
const { date } = useFormat()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.verification.seo'), robots: 'noindex' })

const { data, status, error, refresh } = await useAsyncData('my-verification', () => api<{ data: VerificationState }>('/my/verification').then(r => r.data))
const identity = computed(() => data.value?.identity)
const state = computed(() => identity.value?.status ?? 'none')
const showForm = computed(() => state.value === 'none' || state.value === 'rejected')
const auth = useAuthStore()

async function reload() {
  await refresh()
  await auth.fetchMe()
}
async function onSubmitted() {
  await refresh()
  await auth.fetchMe()
  if (import.meta.client) window.scrollTo({ top: 0, behavior: 'smooth' })
}
const stateTone = computed(() => ({
  approved: 'border-leaf-200! bg-leaf-50! dark:border-leaf-800! dark:bg-leaf-950/50!',
  pending: 'border-honey-200! bg-honey-50! dark:border-honey-800! dark:bg-honey-950/50!',
  rejected: 'border-pomegranate-200! bg-pomegranate-50! dark:border-pomegranate-800! dark:bg-pomegranate-950/50!',
  none: 'border-brand-200! bg-brand-50! dark:border-brand-800! dark:bg-brand-950/50!',
}[state.value]))
const stateIcon = computed(() => ({ approved: 'i-lucide-shield-check', pending: 'i-lucide-hourglass', rejected: 'i-lucide-shield-alert', none: 'i-lucide-shield' }[state.value]))
</script>

<template>
  <div>
    <DashboardPageHead :title="t('dashboard.verification.title')" :subtitle="t('dashboard.verification.subtitle')" />

    <SearchSkeleton v-if="status === 'pending'" />
    <div v-else-if="error || !data" class="surface px-6 py-10 text-center" role="alert">
      <p class="text-muted">{{ t('dashboard.common.error') }}</p>
      <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="refresh()" />
    </div>
    <div v-else class="space-y-8">
      <!-- Shaxsni tasdiqlash -->
      <section aria-labelledby="identity-h">
        <h2 id="identity-h" class="mb-3 font-display text-xl font-extrabold text-highlighted">{{ t('dashboard.verification.identityTitle') }}</h2>
        <div class="surface border p-5 sm:p-7" :class="stateTone">
          <div class="flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/70 text-brand-700 dark:bg-white/10 dark:text-brand-200"><UIcon :name="stateIcon" class="size-6" aria-hidden="true" /></span>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-display text-lg font-extrabold text-highlighted">{{ t(`dashboard.overview.verif.${state}.title`) }}</h3>
                <DashboardStatusBadge kind="verification" :status="state" />
              </div>
              <p class="mt-1 text-muted">{{ t(`dashboard.verification.state.${state}`) }}</p>
              <p v-if="identity?.submitted_at && state !== 'none'" class="mt-1 text-sm text-dimmed">{{ t('dashboard.verification.submittedAt', { date: date(identity.submitted_at) }) }}</p>
              <p v-if="state === 'rejected' && identity?.rejection_reason" class="mt-3 rounded-xl bg-white/70 px-3 py-2 text-sm text-pomegranate-700 dark:bg-white/5 dark:text-pomegranate-300">
                <strong>{{ t('dashboard.verification.rejectionReason') }}:</strong> {{ identity.rejection_reason }}
              </p>
            </div>
          </div>
        </div>
        <div v-if="showForm" class="surface mt-4 p-5 sm:p-7">
          <DashboardIdentityForm @submitted="onSubmitted" />
        </div>
      </section>

      <!-- Hujjatlar -->
      <section aria-labelledby="docs-h">
        <h2 id="docs-h" class="font-display text-xl font-extrabold text-highlighted">{{ t('dashboard.verification.docsTitle') }}</h2>
        <p class="mb-4 mt-1 text-muted">{{ t('dashboard.verification.docsText') }}</p>
        <div class="grid gap-4 xl:grid-cols-2">
          <DashboardDocumentCard v-for="b in data.available_badges" :key="b.slug" :badge="b" :docs="data.documents.filter(d => d.badge?.slug === b.slug)" @changed="reload" />
        </div>
      </section>
    </div>
  </div>
</template>
