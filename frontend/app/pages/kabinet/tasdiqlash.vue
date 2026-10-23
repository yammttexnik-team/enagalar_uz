<script setup lang="ts">
const { t } = useI18n()
const api = useApi()
const { date } = useFormat()
const localizedName = useLocalizedName()
const { toastError, ok } = useApiError()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.verification.seo'), robots: 'noindex' })

const { data, status, error, refresh } = await useAsyncData('my-verification', () => api<{ data: VerificationState }>('/my/verification').then(r => r.data))
const identity = computed(() => data.value?.identity)
const state = computed(() => identity.value?.status ?? 'none')
const showForm = computed(() => state.value === 'none' || state.value === 'rejected')
const auth = useAuthStore()

async function onSubmitted() {
  await refresh()
  await auth.fetchMe()
  if (import.meta.client) window.scrollTo({ top: 0, behavior: 'smooth' })
}
const badges = computed(() => data.value?.badges ?? [])
const pendingBadges = computed(() => badges.value.filter(b => b.is_public === null))
const answeredBadges = computed(() => badges.value.filter(b => b.is_public !== null))
const saving = ref<string | null>(null)
async function setPublic(slug: string, isPublic: boolean) {
  saving.value = slug
  try {
    const res = await api<{ data: MyBadge }>(`/my/badges/${slug}`, { method: 'PATCH', body: { is_public: isPublic } })
    if (data.value) data.value = { ...data.value, badges: data.value.badges.map(b => (b.slug === slug ? { ...b, ...res.data } : b)) }
    ok(t(isPublic ? 'dashboard.verification.savedPublic' : 'dashboard.verification.savedHidden'))
    await auth.fetchMe()
  }
  catch (e) { toastError(e) }
  finally { saving.value = null }
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

      <!-- Tasdiqlangan maʼlumotlar -->
      <section aria-labelledby="certs-h">
        <h2 id="certs-h" class="font-display text-xl font-extrabold text-highlighted">{{ t('dashboard.verification.certsTitle') }}</h2>
        <p class="mb-4 mt-1 text-muted">{{ t('dashboard.verification.certsText') }}</p>
        <div v-if="!badges.length" class="surface px-6 py-8 text-center text-muted">{{ t('dashboard.verification.certsEmpty') }}</div>
        <div v-else class="space-y-3">
          <article v-for="b in pendingBadges" :key="b.slug" class="surface border border-brand-200! bg-brand-50! p-5 dark:border-brand-800! dark:bg-brand-950/50!">
            <p class="font-display text-lg font-extrabold text-highlighted">✅ {{ t('dashboard.verification.askText', { name: localizedName(b.name) }) }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <UButton icon="i-lucide-eye" :loading="saving === b.slug" :disabled="saving !== null" :label="t('dashboard.verification.askYes')" @click="setPublic(b.slug, true)" />
              <UButton color="neutral" variant="outline" icon="i-lucide-eye-off" :disabled="saving !== null" :label="t('dashboard.verification.askNo')" @click="setPublic(b.slug, false)" />
            </div>
          </article>
          <article v-for="b in answeredBadges" :key="b.slug" class="surface flex flex-wrap items-center gap-3 p-4">
            <div class="min-w-0 flex-1">
              <h3 class="font-bold text-highlighted">{{ localizedName(b.name) }}</h3>
            </div>
            <UBadge :color="b.is_public ? 'success' : 'neutral'" variant="subtle" :icon="b.is_public ? 'i-lucide-eye' : 'i-lucide-eye-off'" :label="b.is_public ? t('dashboard.verification.statusPublic') : t('dashboard.verification.statusHidden')" />
            <UButton size="sm" color="neutral" variant="outline" :loading="saving === b.slug" :disabled="saving !== null" :label="b.is_public ? t('dashboard.verification.makeHidden') : t('dashboard.verification.makePublic')" @click="setPublic(b.slug, !b.is_public)" />
          </article>
        </div>
      </section>
    </div>
  </div>
</template>
