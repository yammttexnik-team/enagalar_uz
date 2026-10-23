<script setup lang="ts">
import type { ImportedItem } from '~/components/imported/Card.vue'

/** Telegram kanaldan olingan e'lon: kontakt yo'q, faqat asl postga havola. */
const { t } = useI18n()
const route = useRoute()
const api = useApi()
const auth = useAuthStore()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const config = useRuntimeConfig()
const { salary, enumLabel, relativeTime, date, childAge } = useFormat()

const loadFailed = ref(false)
const notFound = ref(false)
const { data: listing, refresh } = await useAsyncData(`imported-item-${route.params.id}`, async () => {
  try {
    loadFailed.value = false
    return (await api<{ data: ImportedItem }>(`/imported/${route.params.id}`)).data
  }
  catch (e: unknown) {
    if ((e as { statusCode?: number }).statusCode === 404) notFound.value = true
    else loadFailed.value = true
    return null
  }
})
if (notFound.value) throw createError({ statusCode: 404, statusMessage: t('imported.detail.notFound'), fatal: true })

const l = computed(() => listing.value!)
const place = computed(() => [l.value.district, l.value.region].filter(Boolean).map(p => localizedName(p!.name)).join(', '))
const heading = computed(() => l.value.title || t(`imported.fallbackTitle.${l.value.type}`))
const priceText = computed(() => salary(l.value.price))
const backTo = computed(() => localePath(l.value.type === 'nanny' ? '/enagalar' : '/vakansiyalar'))

const title = computed(() => !listing.value ? '' : t('imported.detail.seoTitle', { title: heading.value, place: place.value }))
const description = computed(() => !listing.value ? '' : (l.value.description || heading.value).slice(0, 160))
const canonical = computed(() => !listing.value ? '' : config.public.siteUrl + localePath(`/elon/${l.value.id}`))
useSeoMeta({ title, description, ogTitle: title, ogDescription: description, ogUrl: canonical })
useHead({ link: [{ rel: 'canonical', href: canonical }] })

const facts = computed(() => [
  { icon: 'i-lucide-map-pin', label: t('vacancy.detail.location'), value: place.value },
  { icon: 'i-lucide-briefcase', label: t('vacancy.detail.format'), value: l.value.work_format ? enumLabel('workFormat', l.value.work_format) : null },
  { icon: 'i-lucide-clock', label: t('vacancy.detail.schedule'), value: l.value.schedule },
  { icon: 'i-lucide-baby', label: t('vacancy.detail.children'), value: l.value.children_count ? `${t('vacancy.children', { n: l.value.children_count })}${l.value.children_ages.length ? ` · ${l.value.children_ages.map(a => childAge(a)).join(', ')}` : ''}` : null },
  { icon: 'i-lucide-users', label: t('imported.detail.ageGroups'), value: l.value.age_groups.map(a => enumLabel('ageGroup', a)).join(', ') },
  { icon: 'i-lucide-languages', label: t('vacancy.detail.languages'), value: l.value.languages.map(x => enumLabel('language', x)).join(', ') },
  { icon: 'i-lucide-award', label: t('imported.detail.experience'), value: l.value.experience_years ? t('imported.detail.years', { n: l.value.experience_years }) : null },
  { icon: 'i-lucide-user', label: t('imported.detail.age'), value: l.value.person_age ? t('imported.detail.years', { n: l.value.person_age }) : null },
  { icon: 'i-lucide-house', label: t('vacancy.detail.liveIn'), value: l.value.live_in ? '✓' : null },
].filter(f => f.value))

// "Bu mening e'lonim" — kirgan bo'lsa kabinetdagi topilgan e'lonlarga
const claimTo = computed(() => auth.isLoggedIn
  ? localePath('/kabinet/topilgan-elonlar')
  : localePath({ path: '/kirish', query: { redirect: '/kabinet/topilgan-elonlar' } }))

// --- O'chirishni so'rash ---
const removeOpen = ref(false)
const reason = ref('')
const sending = ref(false)
const removed = ref(false)
const failed = ref(false)
async function sendRemoval() {
  sending.value = true
  failed.value = false
  try {
    await api(`/imported/${l.value.id}/removal-request`, { method: 'POST', body: { reason: reason.value.trim() || undefined } })
    removed.value = true
  }
  catch { failed.value = true }
  finally { sending.value = false }
}
</script>

<template>
  <div v-if="listing" class="bg-linear-to-b from-brand-50/60 to-default dark:from-brand-950/60">
    <UContainer class="pb-16 pt-6 sm:pt-8">
      <NuxtLink :to="backTo" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-highlighted">
        <UIcon name="i-lucide-arrow-left" class="size-4" aria-hidden="true" />{{ t('imported.detail.back') }}
      </NuxtLink>

      <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
        <div class="space-y-6">
          <section class="surface flex flex-col gap-5 p-6 sm:flex-row sm:items-start sm:p-8">
            <ImportedIllustration :type="l.type" size="lg" />
            <div class="min-w-0 flex-1">
              <ImportedSourceBadge :channel="l.source.channel" />
              <h1 class="h1-title mt-3 text-highlighted">{{ heading }}</h1>
              <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-muted">
                <span v-if="place" class="inline-flex items-center gap-1.5"><UIcon name="i-lucide-map-pin" class="size-4" aria-hidden="true" />{{ place }}</span>
                <span>· {{ relativeTime(l.source.posted_at || l.published_at) }}</span>
              </p>
              <p class="mt-4 text-sm text-muted" data-testid="imported-source-line">
                {{ t('imported.detail.source', { channel: l.source.channel, date: date(l.source.posted_at || l.published_at) }) }}
              </p>
            </div>
          </section>

          <section v-if="facts.length" class="surface p-6 sm:p-8">
            <h2 class="h3-title text-highlighted">{{ t('vacancy.detail.details') }}</h2>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
              <div v-for="f in facts" :key="f.label" class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                  <UIcon :name="f.icon" class="size-5" aria-hidden="true" />
                </span>
                <div>
                  <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ f.label }}</dt>
                  <dd class="mt-0.5 font-medium text-highlighted">{{ f.value }}</dd>
                </div>
              </div>
            </dl>
          </section>

          <section v-if="l.description" class="surface p-6 sm:p-8">
            <h2 class="h3-title text-highlighted">{{ t('vacancy.detail.description') }}</h2>
            <p class="mt-3 whitespace-pre-line text-base leading-relaxed text-default">{{ l.description }}</p>
          </section>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-20">
          <section class="surface p-6">
            <template v-if="l.price.min || l.price.max">
              <p class="eyebrow text-muted">{{ t(l.type === 'nanny' ? 'imported.detail.price' : 'vacancy.detail.salary') }}</p>
              <p class="mt-1.5 font-display text-xl font-black tabular-nums text-highlighted"><AppText :text="priceText" /></p>
            </template>
            <UButton
              block
              size="xl"
              class="mt-5 justify-center first:mt-0"
              icon="i-lucide-external-link"
              :label="t('imported.detail.open')"
              :to="l.source.url"
              target="_blank"
              rel="noopener"
              external
            />
            <p class="mt-2 text-center text-xs text-muted">{{ t('imported.detail.openHint') }}</p>
            <UButton
              block
              size="xl"
              color="neutral"
              variant="outline"
              class="mt-4 justify-center"
              icon="i-lucide-user-check"
              :label="t('imported.detail.mine')"
              :to="claimTo"
            />
            <div class="mt-4 text-center">
              <button type="button" class="text-sm text-muted underline underline-offset-2 hover:text-highlighted" @click="removeOpen = true">
                {{ t('imported.detail.removeLink') }}
              </button>
            </div>
          </section>

          <section class="rounded-3xl border border-honey-200 bg-honey-50 p-5 dark:border-honey-800 dark:bg-honey-950">
            <p class="flex items-center gap-2 font-semibold text-honey-800 dark:text-honey-200">
              <UIcon name="i-lucide-shield-alert" class="size-5" aria-hidden="true" />{{ t('imported.detail.noticeTitle') }}
            </p>
            <p class="mt-2 text-sm leading-relaxed text-honey-800 dark:text-honey-200">{{ t('imported.detail.notice') }}</p>
          </section>
        </aside>
      </div>
    </UContainer>

    <UModal v-model:open="removeOpen" :title="t('imported.remove.title')" :description="removed ? undefined : t('imported.remove.text')">
      <template #body>
        <div v-if="removed" class="py-4 text-center" role="status">
          <UIcon name="i-lucide-circle-check" class="size-10 text-leaf-600" aria-hidden="true" />
          <p class="mt-3 font-semibold text-highlighted">{{ t('imported.remove.thanks') }}</p>
          <p class="mt-1 text-sm text-muted">{{ t('imported.remove.thanksText') }}</p>
        </div>
        <template v-else>
          <UFormField :label="t('imported.remove.reason')" :hint="`${reason.length}/500`">
            <UTextarea v-model="reason" :rows="4" :maxlength="500" autoresize class="w-full" :placeholder="t('imported.remove.placeholder')" />
          </UFormField>
          <p v-if="failed" class="mt-3 text-sm text-error" role="alert">{{ t('dashboard.common.error') }}</p>
        </template>
      </template>
      <template #footer>
        <div class="flex w-full justify-end gap-2">
          <UButton v-if="removed" :label="t('imported.remove.back')" :to="backTo" />
          <template v-else>
            <UButton color="neutral" variant="outline" :label="t('vacancy.detail.cancel')" @click="removeOpen = false" />
            <UButton color="error" icon="i-lucide-trash-2" :loading="sending" :label="t('imported.remove.send')" @click="sendRemoval" />
          </template>
        </div>
      </template>
    </UModal>
  </div>
  <UContainer v-else-if="loadFailed" class="max-w-lg py-20 text-center">
    <p class="text-lg text-muted">{{ t('dashboard.common.error') }}</p>
    <UButton class="mt-6" icon="i-lucide-refresh-cw" :label="t('search.retry')" @click="refresh()" />
  </UContainer>
</template>
