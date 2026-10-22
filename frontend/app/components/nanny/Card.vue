<script setup lang="ts">
import type { Badge, LocalizedName } from '~/composables/useApi'

export interface NannyItem {
  id: number
  name: string
  age: number | null
  region?: { slug: string, name: LocalizedName } | null
  district?: { slug: string, name: LocalizedName } | null
  experience_years: number
  price: { amount: number | null, unit: string | null }
  work_formats: string[]
  languages: string[]
  live_in: boolean
  is_available: boolean
  rating: number
  reviews_count: number
  badges: Badge[]
}

const props = defineProps<{ nanny: NannyItem }>()
const { t } = useI18n()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { price, plural, enumLabel } = useFormat()

const place = computed(() => [props.nanny.district, props.nanny.region].filter(Boolean).map(p => localizedName(p!.name)).slice(0, 2).join(', '))
const meta = computed(() => [props.nanny.age ? plural('format.ageYears', props.nanny.age) : null, place.value].filter(Boolean).join(' · '))
const shownBadges = computed(() => props.nanny.badges.slice(0, 4))
</script>

<template>
  <article class="surface group relative flex flex-col gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)]">
    <div class="flex items-start gap-3.5">
      <NannyAvatar :name="nanny.name" :id="nanny.id" />
      <div class="min-w-0 flex-1 pr-8">
        <h3 class="truncate font-display text-lg font-extrabold leading-tight text-highlighted">
          <NuxtLink :to="localePath(`/enagalar/${nanny.id}`)" class="after:absolute after:inset-0 after:rounded-3xl focus-visible:outline-2 focus-visible:outline-primary">
            {{ nanny.name }}
          </NuxtLink>
        </h3>
        <p class="mt-0.5 flex items-start gap-1 text-sm text-muted">
          <UIcon name="i-lucide-map-pin" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
          <span class="min-w-0">{{ meta }}</span>
        </p>
      </div>
      <SearchFavoriteButton type="nanny" :id="nanny.id" class="absolute right-3 top-3 z-10" />
    </div>

    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
      <p class="font-display text-xl font-black tabular-nums text-highlighted"><AppText :text="price(nanny.price.amount, nanny.price.unit)" /></p>
      <p class="text-sm text-muted">
        {{ nanny.experience_years ? plural('format.yearsExp', nanny.experience_years) : t('nanny.noExperience') }}
      </p>
    </div>

    <div class="flex flex-wrap gap-1.5">
      <UBadge v-for="f in nanny.work_formats" :key="f" color="neutral" variant="subtle" :label="enumLabel('workFormat', f)" />
    </div>

    <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-default pt-3.5">
      <ul v-if="shownBadges.length" class="flex gap-1.5" :aria-label="t('nanny.detail.badges')">
        <li v-for="b in shownBadges" :key="b.slug"><SearchBadgeChip :badge="b" icon-only /></li>
      </ul>
      <p v-if="nanny.languages.length" class="text-xs font-semibold uppercase tracking-wide text-muted">{{ nanny.languages.join(' · ') }}</p>
      <p v-if="nanny.rating > 0" class="ml-auto inline-flex items-center gap-1 text-sm font-semibold text-highlighted">
        <UIcon name="i-lucide-star" class="size-4 fill-honey-400 text-honey-500" aria-hidden="true" />
        {{ nanny.rating.toFixed(1) }}
        <span class="font-normal text-muted">({{ nanny.reviews_count }})</span>
      </p>
      <p
        v-if="nanny.is_available"
        class="inline-flex items-center gap-1.5 text-sm font-semibold text-leaf-700 dark:text-leaf-400"
        :class="nanny.rating > 0 ? '' : 'ml-auto'"
      >
        <span class="size-2 rounded-full bg-leaf-500 dark:bg-leaf-400" aria-hidden="true" />
        {{ t('nanny.available') }}
      </p>
    </div>
  </article>
</template>
