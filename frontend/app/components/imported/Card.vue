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
const { salary, enumLabel, relativeTime } = useFormat()

const place = computed(() => [props.item.district, props.item.region].filter(Boolean).map(p => localizedName(p!.name)).join(', '))
const title = computed(() => props.item.title || t(`imported.fallbackTitle.${props.item.type}`))
</script>

<template>
  <article class="surface group relative flex flex-col gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)]" data-testid="imported-card">
    <div class="flex items-start gap-4">
      <ImportedIllustration :type="item.type" />
      <div class="min-w-0 flex-1">
        <ImportedSourceBadge :channel="item.source.channel" />
        <h3 class="mt-2 font-display text-lg font-extrabold leading-snug text-highlighted">
          <NuxtLink :to="localePath(`/elon/${item.id}`)" class="after:absolute after:inset-0 after:rounded-3xl focus-visible:outline-2 focus-visible:outline-primary">
            {{ title }}
          </NuxtLink>
        </h3>
        <p v-if="place" class="mt-1 flex items-start gap-1 text-sm text-muted">
          <UIcon name="i-lucide-map-pin" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />{{ place }}
        </p>
      </div>
    </div>

    <p v-if="item.price.min || item.price.max" class="font-display text-lg font-black tabular-nums text-highlighted"><AppText :text="salary(item.price)" /></p>

    <div class="flex flex-wrap gap-1.5">
      <UBadge v-if="item.work_format" color="neutral" variant="subtle" :label="enumLabel('workFormat', item.work_format)" />
      <UBadge v-if="item.live_in" color="neutral" variant="subtle" :label="t('vacancy.liveIn')" />
      <UBadge v-if="item.is_urgent" color="secondary" variant="subtle" :label="t('vacancy.urgent')" />
    </div>

    <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-default pt-3.5 text-sm text-muted">
      <span class="text-xs font-semibold uppercase tracking-wide">{{ item.languages.join(' · ') }}</span>
      <span>{{ relativeTime(item.source.posted_at || item.published_at) }}</span>
    </div>
  </article>
</template>
