<script setup lang="ts">
import type { ImportedItem } from '~/components/imported/Card.vue'

/** Telegram kanallardan topilgan, kontakti foydalanuvchiga mos e'lonlar: tasdiqlash yoki o'chirish. */
const { t } = useI18n()
const api = useApi()
const auth = useAuthStore()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { salary, relativeTime } = useFormat()
const { toastError, ok } = useApiError()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('imported.claims.seo'), robots: 'noindex' })

const { data, status, refresh } = await useAsyncData('imported-claims', () => api<{ data: ImportedItem[] }>('/my/imported-claims').then(r => r.data))
const items = computed(() => data.value ?? [])
const busy = ref<string | null>(null)

const place = (i: ImportedItem) => [i.district, i.region].filter(Boolean).map(p => localizedName(p!.name)).join(', ')

async function accept(i: ImportedItem) {
  busy.value = `accept-${i.id}`
  try {
    const res = await api<{ data: { type: 'vacancy' | 'nanny', id: number } }>(`/my/imported-claims/${i.id}/accept`, { method: 'POST' })
    // Yangi rol va hisoblagich yangilanadi
    await auth.fetchMe()
    ok(t('imported.claims.accepted'))
    await navigateTo(localePath(res.data.type === 'vacancy' ? `/kabinet/vakansiyalar/${res.data.id}` : '/kabinet/anketa'))
  }
  catch (e) {
    toastError(e)
    await refresh()
  }
  finally { busy.value = null }
}

async function dismiss(i: ImportedItem, action: 'not_mine' | 'remove') {
  busy.value = `${action}-${i.id}`
  try {
    await api(`/my/imported-claims/${i.id}/dismiss`, { method: 'POST', body: { action } })
    data.value = items.value.filter(x => x.id !== i.id)
    if (auth.user) auth.user.imported_claims_count = items.value.length
    ok(t(action === 'remove' ? 'imported.claims.removed' : 'imported.claims.dismissed'))
  }
  catch (e) {
    toastError(e)
    await refresh()
  }
  finally { busy.value = null }
}
</script>

<template>
  <div>
    <DashboardPageHead :title="t('imported.claims.title')" :subtitle="t('imported.claims.subtitle')" />

    <div v-if="status === 'pending'" class="surface h-40 animate-pulse" />
    <div v-else-if="!items.length" class="surface px-6 py-12 text-center" data-testid="claims-empty">
      <UIcon name="i-lucide-search-check" class="size-10 text-muted" aria-hidden="true" />
      <h2 class="h3-title mt-4 text-highlighted">{{ t('imported.claims.emptyTitle') }}</h2>
      <p class="mx-auto mt-2 max-w-md text-muted">{{ t('imported.claims.emptyText') }}</p>
    </div>
    <ul v-else class="space-y-4">
      <li v-for="i in items" :key="i.id" class="surface p-5 sm:p-6" data-testid="claim-item">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
          <ImportedIllustration :type="i.type" />
          <div class="min-w-0 flex-1">
            <ImportedSourceBadge :channel="i.source.channel" />
            <h2 class="mt-2 font-display text-lg font-extrabold text-highlighted">{{ i.title || t(`imported.fallbackTitle.${i.type}`) }}</h2>
            <p class="mt-1 text-sm text-muted">
              <template v-if="place(i)">{{ place(i) }} · </template>{{ relativeTime(i.source.posted_at || i.published_at) }}
              <template v-if="i.price.min || i.price.max"> · <AppText :text="salary(i.price)" /></template>
            </p>
            <p class="mt-3 rounded-xl bg-brand-50 p-3 text-sm leading-relaxed text-brand-800 dark:bg-brand-950 dark:text-brand-100">
              {{ t('imported.claims.explain', { channel: i.source.channel }) }}
            </p>
            <a :href="i.source.url" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
              <UIcon name="i-lucide-external-link" class="size-3.5" aria-hidden="true" />{{ t('imported.detail.open') }}
            </a>
            <div class="mt-4 flex flex-wrap gap-2">
              <UButton icon="i-lucide-check" :loading="busy === `accept-${i.id}`" :disabled="!!busy" :label="t('imported.claims.accept')" @click="accept(i)" />
              <UButton color="neutral" variant="outline" icon="i-lucide-x" :loading="busy === `not_mine-${i.id}`" :disabled="!!busy" :label="t('imported.claims.notMine')" @click="dismiss(i, 'not_mine')" />
              <UButton color="error" variant="ghost" icon="i-lucide-trash-2" :loading="busy === `remove-${i.id}`" :disabled="!!busy" :label="t('imported.claims.remove')" @click="dismiss(i, 'remove')" />
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
</template>
