<script setup lang="ts">
import type { BonusWallet, SurveyListItem } from '~/composables/useBonus'

const { t } = useI18n()
const localePath = useLocalePath()
const auth = useAuthStore()
const api = useApi()
const { money } = useFormat()
const { balance } = useBonus()
const { ok } = useApiError()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('bonus.seo'), robots: 'noindex' })

const { data: wallet, status, error, refresh } = await useAsyncData('bonus-wallet', () => api<BonusWallet>('/me/bonus'))
const { data: surveys } = await useAsyncData('bonus-surveys', () => api<{ data: SurveyListItem[] }>('/surveys').then(r => r.data).catch(() => [] as SurveyListItem[]), { default: () => [] as SurveyListItem[] })

const items = ref<BonusWallet['transactions']['data']>([])
const page = ref(1)
const lastPage = ref(1)
watch(wallet, (w) => {
  if (!w) return
  items.value = w.transactions.data
  page.value = w.transactions.meta.current_page
  lastPage.value = w.transactions.meta.last_page
  balance.value = w.balance
}, { immediate: true })

const loadingMore = ref(false)
async function more() {
  loadingMore.value = true
  try {
    const next = await api<BonusWallet>('/me/bonus', { query: { page: page.value + 1 } })
    items.value = [...items.value, ...next.transactions.data]
    page.value = next.transactions.meta.current_page
    lastPage.value = next.transactions.meta.last_page
  }
  catch { /* qayta bosish mumkin */ }
  finally { loadingMore.value = false }
}

const isNanny = computed(() => !!auth.user?.roles.includes('nanny'))
const isParent = computed(() => !!auth.user?.roles.includes('parent'))
const mySurvey = computed(() => surveys.value.find(s => s.audience === (isNanny.value ? 'nanny' : 'parent')) ?? surveys.value[0])
const listingTo = computed(() => localePath(isNanny.value ? '/kabinet/anketa' : '/kabinet/vakansiyalar/yangi'))

// Server va brauzer bir xil chiqarishi uchun (hydration) vaqt zonasi qat'iy: Toshkent.
const fmtDate = (iso: string) => {
  const p = Object.fromEntries(new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Tashkent', day: '2-digit', month: '2-digit', year: 'numeric' }).formatToParts(new Date(iso)).map(x => [x.type, x.value]))
  return `${p.day}.${p.month}.${p.year}`
}
const link = computed(() => wallet.value?.referral_link ?? '')
const telegramShare = computed(() => `https://t.me/share/url?url=${encodeURIComponent(link.value)}&text=${encodeURIComponent(t('bonus.share.text'))}`)
async function copy() {
  try {
    await navigator.clipboard.writeText(link.value)
    ok(t('bonus.share.copied'))
  }
  catch { /* clipboard ruxsati yoʻq */ }
}
</script>

<template>
  <div>
    <DashboardPageHead :title="t('bonus.title')" :subtitle="t('bonus.subtitle')" />

    <SearchSkeleton v-if="status === 'pending'" />
    <div v-else-if="error || !wallet" class="surface px-6 py-10 text-center" role="alert">
      <p class="text-muted">{{ t('dashboard.common.error') }}</p>
      <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="refresh()" />
    </div>

    <template v-else>
      <!-- Balans -->
      <section class="surface mb-6 flex flex-col gap-2 bg-apricot-50! p-6 dark:bg-apricot-950/40! sm:p-8" :aria-label="t('bonus.balance')">
        <p class="eyebrow text-apricot-700 dark:text-apricot-300">{{ t('bonus.balance') }}</p>
        <p class="font-display text-4xl font-black tabular-nums text-highlighted sm:text-5xl" data-testid="bonus-balance">
          {{ money(wallet.balance) }} <span class="text-xl font-extrabold text-muted"><AppText :text="t('bonus.som')" /></span>
        </p>
        <p class="text-sm text-muted">{{ t('bonus.note') }} {{ t('bonus.expiryNote') }}
          <NuxtLink :to="localePath('/bonus-shartlari')" class="font-semibold text-primary underline underline-offset-4">{{ t('terms.link') }}</NuxtLink>
        </p>
      </section>

      <!-- Qanday olish -->
      <h2 class="mb-3 font-display text-lg font-extrabold text-highlighted"><AppText :text="t('bonus.earnTitle')" /></h2>
      <section class="mb-8 grid gap-4 lg:grid-cols-3">
        <article class="surface flex flex-col gap-3 p-5">
          <span class="grid size-11 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-clipboard-list" class="size-5" aria-hidden="true" /></span>
          <h3 class="font-display text-base font-extrabold text-highlighted"><AppText :text="t('bonus.earn.survey.title')" /></h3>
          <p class="text-sm text-muted">{{ t('bonus.earn.survey.text') }}</p>
          <p v-if="mySurvey && !mySurvey.completed" class="text-sm font-bold text-apricot-700 dark:text-apricot-300">{{ t('survey.bonus', { n: money(mySurvey.bonus) }) }}</p>
          <UButton v-if="mySurvey && !mySurvey.completed" class="mt-auto justify-center" icon="i-lucide-arrow-right" :label="t('bonus.earn.survey.cta')" :to="localePath(`/sorovnoma/${mySurvey.slug}`)" />
          <UBadge v-else-if="mySurvey" class="mt-auto w-fit" color="success" variant="subtle" icon="i-lucide-check" :label="t('bonus.earn.survey.done')" />
        </article>

        <article class="surface flex flex-col gap-3 p-5">
          <span class="grid size-11 place-items-center rounded-2xl bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200"><UIcon name="i-lucide-megaphone" class="size-5" aria-hidden="true" /></span>
          <h3 class="font-display text-base font-extrabold text-highlighted"><AppText :text="isNanny || !isParent ? t('bonus.earn.listing.titleNanny') : t('bonus.earn.listing.titleParent')" /></h3>
          <p class="text-sm text-muted">{{ t('bonus.earn.listing.text') }}</p>
          <UButton class="mt-auto justify-center" color="neutral" variant="outline" icon="i-lucide-arrow-right" :label="isNanny || !isParent ? t('bonus.earn.listing.ctaNanny') : t('bonus.earn.listing.ctaParent')" :to="listingTo" />
        </article>

        <article class="surface flex flex-col gap-3 p-5">
          <span class="grid size-11 place-items-center rounded-2xl bg-leaf-100 text-leaf-700 dark:bg-leaf-900 dark:text-leaf-200"><UIcon name="i-lucide-users" class="size-5" aria-hidden="true" /></span>
          <h3 class="font-display text-base font-extrabold text-highlighted"><AppText :text="t('bonus.earn.referral.title')" /></h3>
          <p class="text-sm text-muted">{{ t('bonus.earn.referral.text') }}</p>
          <div class="mt-auto">
            <label for="ref-link" class="eyebrow text-muted">{{ t('bonus.share.link') }}</label>
            <input id="ref-link" :value="link" readonly class="mt-1 w-full rounded-lg border border-default bg-elevated px-3 py-2 text-sm" @focus="($event.target as HTMLInputElement).select()">
            <div class="mt-2 flex flex-wrap gap-2">
              <UButton size="sm" color="neutral" variant="outline" icon="i-lucide-copy" :label="t('bonus.share.copy')" @click="copy" />
              <UButton size="sm" icon="i-lucide-send" :label="t('bonus.share.telegram')" :to="telegramShare" target="_blank" rel="noopener" />
            </div>
          </div>
        </article>
      </section>

      <!-- Tarix -->
      <h2 class="mb-3 font-display text-lg font-extrabold text-highlighted">{{ t('bonus.history.title') }}</h2>
      <section class="surface divide-y divide-default overflow-hidden" :aria-label="t('bonus.history.title')">
        <p v-if="!items.length" class="px-6 py-10 text-center text-muted">{{ t('bonus.history.empty') }}</p>
        <ul v-else class="divide-y divide-default">
          <li v-for="tx in items" :key="tx.id" class="flex items-center gap-4 px-5 py-4">
            <span class="grid size-10 shrink-0 place-items-center rounded-full" :class="tx.amount > 0 ? 'bg-leaf-100 text-leaf-700 dark:bg-leaf-900 dark:text-leaf-200' : 'bg-pomegranate-100 text-pomegranate-700 dark:bg-pomegranate-900 dark:text-pomegranate-200'">
              <UIcon :name="tx.amount > 0 ? 'i-lucide-plus' : 'i-lucide-minus'" class="size-5" aria-hidden="true" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-highlighted">{{ t(`bonus.type.${tx.type}`) }}</p>
              <p class="truncate text-sm text-muted">
                {{ fmtDate(tx.created_at) }}<template v-if="tx.type === 'manual' && tx.description"> · {{ tx.description }}</template>
                <template v-if="tx.amount > 0 && tx.expires_at"> · {{ t('bonus.history.expires', { date: fmtDate(tx.expires_at) }) }}</template>
              </p>
            </div>
            <p class="font-display font-black tabular-nums" :class="tx.amount > 0 ? 'text-leaf-700 dark:text-leaf-300' : 'text-pomegranate-700 dark:text-pomegranate-300'">
              {{ tx.amount > 0 ? '+' : '−' }}{{ money(Math.abs(tx.amount)) }}
            </p>
          </li>
        </ul>
        <div v-if="page < lastPage" class="p-4 text-center">
          <UButton color="neutral" variant="outline" :loading="loadingMore" :label="t('bonus.history.more')" @click="more" />
        </div>
      </section>
    </template>
  </div>
</template>
