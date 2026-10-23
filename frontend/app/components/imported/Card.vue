<script setup lang="ts">
import type { LocalizedName } from '~/composables/useApi'

/** Telegram kanaldan olingan e'lon (GET /imported). Kontakt yo'q — faqat asl postga havola. */
export interface ImportedItem {
  id: number
  type: 'vacancy' | 'nanny'
  title: string | null
  description: string | null
  region?: { slug: string, name: LocalizedName } | null
  district?: { slug: string, name: LocalizedName } | null
  work_format: string | null
  schedule: string | null
  price: { min: number | null, max: number | null, unit: string | null }
  children_count: number | null
  children_ages: number[]
  age_groups: string[]
  languages: string[]
  experience_years: number | null
  person_age: number | null
  live_in: boolean
  is_urgent: boolean
  source: { channel: string, url: string, posted_at: string | null }
  published_at: string | null
  expires_at: string | null
}

const props = defineProps<{ item: ImportedItem }>()
const { t } = useI18n()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { salary, enumLabel, relativeTime, plural, childAge } = useFormat()

const place = computed(() => [props.item.district, props.item.region].filter(Boolean).map(p => localizedName(p!.name)).slice(0, 2).join(', '))
const title = computed(() => props.item.title || t(`imported.fallbackTitle.${props.item.type}`))
const meta = computed(() => [
  props.item.type === 'nanny' && props.item.person_age ? plural('format.ageYears', props.item.person_age) : null,
  place.value,
].filter(Boolean).join(' · '))

// Narx yonidagi qisqa fakt: enagada tajriba, vakansiyada bolalar yoshi
const fact = computed(() => {
  if (props.item.type === 'nanny') {
    return props.item.experience_years ? plural('format.yearsExp', props.item.experience_years) : ''
  }
  return props.item.children_ages.length ? props.item.children_ages.map(a => childAge(a)).join(', ') : ''
})

// "Turar joyda" formati bilan live_in bayrog'i bir narsani ikki marta ko'rsatmasin
const chips = computed(() => {
  const list: { key: string, label: string, color: 'neutral' | 'secondary' }[] = []
  if (props.item.work_format) list.push({ key: 'wf', label: enumLabel('workFormat', props.item.work_format), color: 'neutral' })
  if (props.item.live_in && props.item.work_format !== 'live_in') list.push({ key: 'live', label: t('vacancy.liveIn'), color: 'neutral' })
  if (props.item.type === 'nanny') props.item.age_groups.slice(0, 3).forEach(g => list.push({ key: `ag-${g}`, label: enumLabel('ageGroup', g), color: 'neutral' }))
  return list
})
const hasPrice = computed(() => !!(props.item.price.min || props.item.price.max))
</script>

<template>
  <article class="surface group relative flex flex-col gap-3.5 p-5 transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)]" data-testid="imported-card">
    <span v-if="item.is_urgent" class="-mb-1 inline-flex w-fit items-center gap-1 rounded-full bg-apricot-100 px-2.5 py-1 text-xs font-bold text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200">
      <UIcon name="i-lucide-zap" class="size-3.5" aria-hidden="true" />{{ t('vacancy.urgent') }}
    </span>

    <div class="flex items-start gap-3.5">
      <ImportedIllustration :type="item.type" />
      <div class="min-w-0 flex-1">
        <h3 class="line-clamp-2 font-display text-lg font-extrabold leading-snug text-highlighted">
          <NuxtLink :to="localePath(`/elon/${item.id}`)" class="after:absolute after:inset-0 after:rounded-3xl focus-visible:outline-2 focus-visible:outline-primary">
            {{ title }}
          </NuxtLink>
        </h3>
        <p v-if="meta" class="mt-0.5 flex items-start gap-1 text-sm text-muted">
          <UIcon name="i-lucide-map-pin" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
          <span class="min-w-0">{{ meta }}</span>
        </p>
      </div>
    </div>

    <div v-if="hasPrice || fact" class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
      <p v-if="hasPrice" class="font-display text-xl font-black tabular-nums text-highlighted"><AppText :text="salary(item.price)" /></p>
      <p v-if="fact" class="text-sm text-muted">{{ fact }}</p>
    </div>

    <p v-if="item.description" class="line-clamp-3 text-sm leading-relaxed text-default">{{ item.description }}</p>

    <div v-if="chips.length" class="flex flex-wrap gap-1.5">
      <UBadge v-for="c in chips" :key="c.key" :color="c.color" variant="subtle" :label="c.label" />
    </div>

    <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-default pt-3 text-xs text-muted">
      <span class="inline-flex min-w-0 items-center gap-1.5">
        <UIcon name="i-lucide-send" class="size-3.5 shrink-0 text-brand-500" aria-hidden="true" />
        <span class="truncate">@{{ item.source.channel }} · {{ t('imported.badge.unverified') }}</span>
      </span>
      <span class="shrink-0">{{ relativeTime(item.source.posted_at || item.published_at) }}</span>
    </div>
  </article>
</template>
