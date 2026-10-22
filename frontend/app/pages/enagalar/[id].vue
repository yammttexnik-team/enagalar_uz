<script setup lang="ts">
import type { NannyItem } from '~/components/nanny/Card.vue'
import type { Skill } from '~/composables/useCatalog'

interface NannyDetail extends NannyItem {
  bio: string | null
  age_groups: string[]
  free_trial_day: boolean
  skills: Skill[]
  published_at: string | null
}

const { t, te } = useI18n()
const route = useRoute()
const api = useApi()
const auth = useAuthStore()
const toast = useToast()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const config = useRuntimeConfig()
const { price, plural, enumLabel, relativeTime } = useFormat()

const { data: nanny } = await useAsyncData(`nanny-${route.params.id}`, async () => {
  try {
    return (await api<{ data: NannyDetail }>(`/nannies/${route.params.id}`)).data
  }
  catch (e: unknown) {
    const status = (e as { statusCode?: number }).statusCode
    throw createError({ statusCode: status === 404 ? 404 : 500, statusMessage: t('nanny.detail.notFound'), fatal: true })
  }
})

const n = computed(() => nanny.value!)
const place = computed(() => [n.value.district, n.value.region].filter(Boolean).map(p => localizedName(p!.name)).join(', '))
const expText = computed(() => (n.value.experience_years ? plural('format.yearsExp', n.value.experience_years) : t('nanny.noExperience')))
const ageText = computed(() => (n.value.age ? plural('format.ageYears', n.value.age) : ''))

const title = computed(() => t('nanny.detail.seoTitle', { name: n.value.name, place: place.value }))
const description = computed(() => t('nanny.detail.seoDescription', { name: n.value.name, age: ageText.value, experience: expText.value, price: price(n.value.price.amount, n.value.price.unit) }))
const canonical = computed(() => config.public.siteUrl + localePath(`/enagalar/${n.value.id}`))
useSeoMeta({ title, description, ogTitle: title, ogDescription: description, ogUrl: canonical })
useHead({ link: [{ rel: 'canonical', href: canonical }] })

const badgeText = (b: NannyDetail['badges'][number]) =>
  (b.description ? localizedName(b.description as never) : '') || (te(`home.trust.badgeText.${b.slug}`) ? t(`home.trust.badgeText.${b.slug}`) : '')

function write() {
  if (!auth.isLoggedIn) return navigateTo(localePath({ path: '/kirish', query: { redirect: route.fullPath } }))
  toast.add({ title: t('nanny.detail.chatSoon'), icon: 'i-lucide-message-circle', color: 'info' })
}
function report() {
  toast.add({ title: t('nanny.detail.reportSoon'), icon: 'i-lucide-flag', color: 'neutral' })
}
</script>

<template>
  <div v-if="nanny" class="bg-linear-to-b from-brand-50/60 to-default dark:from-brand-950/60">
    <UContainer class="pb-16 pt-6 sm:pt-8">
      <NuxtLink :to="localePath('/enagalar')" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-highlighted">
        <UIcon name="i-lucide-arrow-left" class="size-4" aria-hidden="true" />{{ t('nanny.detail.back') }}
      </NuxtLink>

      <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
        <div class="space-y-6">
          <!-- Sarlavha kartasi -->
          <section class="surface p-6 sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
              <NannyAvatar :name="n.name" :id="n.id" size="xl" />
              <div class="min-w-0 flex-1">
                <p v-if="n.is_available" class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-leaf-50 px-2.5 py-1 text-sm font-semibold text-leaf-700 dark:bg-leaf-950 dark:text-leaf-300">
                  <span class="size-2 rounded-full bg-leaf-500 dark:bg-leaf-400" aria-hidden="true" />{{ t('nanny.available') }}
                </p>
                <h1 class="h1-title text-highlighted">{{ n.name }}</h1>
                <p class="mt-1.5 text-muted">{{ [ageText, place].filter(Boolean).join(' · ') }}</p>
                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                  <span class="inline-flex items-center gap-1.5 font-medium text-default">
                    <UIcon name="i-lucide-briefcase" class="size-4 text-primary" aria-hidden="true" />{{ expText }}
                  </span>
                  <span v-if="n.rating > 0" class="inline-flex items-center gap-1 font-semibold text-highlighted">
                    <UIcon name="i-lucide-star" class="size-4 fill-honey-400 text-honey-500" aria-hidden="true" />
                    {{ n.rating.toFixed(1) }} <span class="font-normal text-muted">· {{ t('nanny.detail.reviews', { n: n.reviews_count }) }}</span>
                  </span>
                  <span v-else class="text-muted">{{ t('nanny.detail.noReviews') }}</span>
                </p>
              </div>
            </div>
            <ul v-if="n.badges.length" class="mt-5 flex flex-wrap gap-2">
              <li v-for="b in n.badges" :key="b.slug"><SearchBadgeChip :badge="b" /></li>
            </ul>
            <div class="mt-6 flex flex-wrap gap-3 border-t border-default pt-5">
              <UButton size="xl" icon="i-lucide-message-circle" class="justify-center" :label="t('nanny.detail.write')" @click="write" />
              <SearchFavoriteButton type="nanny" :id="n.id" label class="rounded-xl" />
            </div>
          </section>

          <section class="surface p-6 sm:p-8">
            <h2 class="h3-title text-highlighted">{{ t('nanny.detail.about') }}</h2>
            <p v-if="n.bio" class="mt-3 whitespace-pre-line text-base leading-relaxed text-default">{{ n.bio }}</p>
            <p v-else class="mt-3 text-muted">{{ t('nanny.detail.noBio') }}</p>

            <template v-if="n.skills.length">
              <h3 class="eyebrow mt-7 text-muted">{{ t('nanny.detail.skills') }}</h3>
              <ul class="mt-3 flex flex-wrap gap-2">
                <li v-for="s in n.skills" :key="s.id">
                  <UBadge color="primary" variant="subtle" size="lg" :label="localizedName(s.name)" />
                </li>
              </ul>
            </template>
          </section>

          <section class="surface p-6 sm:p-8">
            <h2 class="h3-title text-highlighted">{{ t('nanny.detail.work') }}</h2>
            <dl class="mt-4 grid gap-x-8 gap-y-5 sm:grid-cols-2">
              <div>
                <dt class="eyebrow text-muted">{{ t('nanny.detail.workFormats') }}</dt>
                <dd class="mt-2 flex flex-wrap gap-1.5">
                  <UBadge v-for="f in n.work_formats" :key="f" color="neutral" variant="subtle" size="lg" :label="enumLabel('workFormat', f)" />
                </dd>
              </div>
              <div>
                <dt class="eyebrow text-muted">{{ t('nanny.detail.ageGroups') }}</dt>
                <dd class="mt-2 flex flex-wrap gap-1.5">
                  <UBadge v-for="a in n.age_groups" :key="a" color="neutral" variant="subtle" size="lg" :label="enumLabel('ageGroup', a)" />
                </dd>
              </div>
              <div>
                <dt class="eyebrow text-muted">{{ t('nanny.detail.languages') }}</dt>
                <dd class="mt-2 flex flex-wrap gap-1.5">
                  <UBadge v-for="l in n.languages" :key="l" color="neutral" variant="subtle" size="lg" :label="enumLabel('language', l)" />
                </dd>
              </div>
            </dl>
            <ul class="mt-6 space-y-2.5 text-[15px] text-default">
              <li v-if="n.live_in" class="flex items-center gap-2.5">
                <UIcon name="i-lucide-house" class="size-5 text-primary" aria-hidden="true" />{{ t('nanny.detail.liveIn') }}
              </li>
              <li v-if="n.free_trial_day" class="flex items-center gap-2.5">
                <UIcon name="i-lucide-gift" class="size-5 text-primary" aria-hidden="true" />{{ t('nanny.detail.freeTrial') }}
              </li>
            </ul>
          </section>

          <section class="surface p-6 sm:p-8">
            <h2 class="h3-title text-highlighted">{{ t('nanny.detail.badges') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ t('nanny.detail.badgesHint') }}</p>
            <ul v-if="n.badges.length" class="mt-5 space-y-4">
              <li v-for="b in n.badges" :key="b.slug" class="flex items-start gap-3.5">
                <SearchBadgeChip :badge="b" icon-only class="mt-0.5 size-9!" />
                <div>
                  <p class="font-semibold text-highlighted">{{ localizedName(b.name) }}</p>
                  <p class="text-sm leading-relaxed text-muted">{{ badgeText(b) }}</p>
                </div>
              </li>
            </ul>
            <p v-else class="mt-4 text-muted">{{ t('nanny.detail.noBadges') }}</p>
          </section>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-20">
          <section class="surface p-6">
            <p class="eyebrow text-muted">{{ t('nanny.detail.price') }}</p>
            <p class="mt-1.5 font-display text-3xl font-black tabular-nums text-highlighted"><AppText :text="price(n.price.amount, n.price.unit)" /></p>
            <UButton block size="xl" class="mt-5 justify-center" icon="i-lucide-message-circle" :label="t('nanny.detail.write')" @click="write" />
            <p v-if="n.published_at" class="mt-4 text-center text-xs text-muted">{{ t('nanny.detail.views', { when: relativeTime(n.published_at) }) }}</p>
          </section>

          <section class="rounded-3xl border border-honey-200 bg-honey-50 p-5 dark:border-honey-800 dark:bg-honey-950">
            <p class="flex items-center gap-2 font-semibold text-honey-800 dark:text-honey-200">
              <UIcon name="i-lucide-shield-alert" class="size-5" aria-hidden="true" />{{ t('nanny.detail.safetyTitle') }}
            </p>
            <p class="mt-2 text-sm leading-relaxed text-honey-800 dark:text-honey-200">{{ t('nanny.detail.safety') }}</p>
          </section>

          <button type="button" class="mx-auto flex items-center gap-1.5 text-sm text-muted underline-offset-4 hover:text-highlighted hover:underline" @click="report">
            <UIcon name="i-lucide-flag" class="size-4" aria-hidden="true" />{{ t('nanny.detail.report') }}
          </button>
        </aside>
      </div>
    </UContainer>
  </div>
</template>
