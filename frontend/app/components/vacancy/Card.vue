<script setup lang="ts">
import type { Badge, LocalizedName } from '~/composables/useApi'

export interface VacancyItem {
  id: number
  title: string
  description: string | null
  region?: { slug: string, name: LocalizedName } | null
  district?: { slug: string, name: LocalizedName } | null
  work_format: string
  schedule: string | null
  salary: { min: number | null, max: number | null, unit: string | null }
  children_count: number
  children_ages: number[]
  languages: string[]
  live_in: boolean
  is_urgent: boolean
  is_top?: boolean
  starts_at: string | null
  published_at: string | null
  expires_at: string | null
  parent: { name: string, badges: Badge[], rating?: number, reviews_count?: number } | null
}

const props = defineProps<{ vacancy: VacancyItem }>()
const { t } = useI18n()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { salary, enumLabel, relativeTime, childAge } = useFormat()

const place = computed(() => [props.vacancy.district, props.vacancy.region].filter(Boolean).map(p => localizedName(p!.name)).slice(0, 2).join(', '))
const ages = computed(() => props.vacancy.children_ages.map(a => childAge(a)).join(', '))
</script>

<template>
  <article class="surface group relative flex flex-col gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)]">
    <div class="flex items-start gap-3 pr-10">
      <div class="min-w-0 flex-1">
        <div v-if="vacancy.is_urgent || vacancy.is_top" class="mb-2 flex flex-wrap gap-1.5">
          <PromoTopChip v-if="vacancy.is_top" />
          <p v-if="vacancy.is_urgent" class="inline-flex items-center gap-1 rounded-full bg-apricot-100 px-2.5 py-1 text-xs font-bold text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200">
            <UIcon name="i-lucide-zap" class="size-3.5" aria-hidden="true" />{{ t('vacancy.urgent') }}
          </p>
        </div>
        <h3 class="font-display text-lg font-extrabold leading-snug text-highlighted">
          <NuxtLink :to="localePath(`/vakansiyalar/${vacancy.id}`)" class="after:absolute after:inset-0 after:rounded-3xl focus-visible:outline-2 focus-visible:outline-primary">
            {{ vacancy.title }}
          </NuxtLink>
        </h3>
        <p class="mt-1 flex items-start gap-1 text-sm text-muted">
          <UIcon name="i-lucide-map-pin" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />{{ place }}
        </p>
      </div>
      <SearchFavoriteButton type="vacancy" :id="vacancy.id" class="absolute right-3 top-3 z-10" />
    </div>

    <p class="font-display text-xl font-black tabular-nums text-highlighted"><AppText :text="salary(vacancy.salary)" /></p>

    <div class="flex flex-wrap gap-1.5">
      <UBadge color="neutral" variant="subtle" :label="enumLabel('workFormat', vacancy.work_format)" />
      <UBadge v-if="vacancy.live_in" color="neutral" variant="subtle" :label="t('vacancy.liveIn')" />
    </div>

    <p class="flex items-start gap-2 text-sm text-default">
      <UIcon name="i-lucide-baby" class="mt-0.5 size-4.5 shrink-0 text-primary" aria-hidden="true" />
      <span>{{ t('vacancy.children', { n: vacancy.children_count }) }}<template v-if="ages"> · {{ ages }}</template></span>
    </p>

    <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-default pt-3.5 text-sm text-muted">
      <span class="text-xs font-semibold uppercase tracking-wide">{{ vacancy.languages.join(' · ') }}</span>
      <span>{{ relativeTime(vacancy.published_at) }}</span>
    </div>
  </article>
</template>
