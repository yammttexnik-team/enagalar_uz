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
  care_location?: string | null
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
const { salary, relativeTime, enumLabel } = useFormat()

const place = computed(() => [props.item.district, props.item.region].filter(Boolean).map(p => localizedName(p!.name)).slice(0, 2).join(', '))
const title = computed(() => props.item.title || t(`imported.fallbackTitle.${props.item.type}`))

const hasPrice = computed(() => !!(props.item.price.min || props.item.price.max))

// Matn asosan kirill bo'lsa lang="ru" (ekran o'qigichlar va tire uchun)
function langOf(text: string | null | undefined) {
  if (!text) return undefined
  const cyr = (text.match(/[\u0400-\u04FF]/g) ?? []).length
  const lat = (text.match(/[A-Za-z]/g) ?? []).length
  return cyr > lat ? 'ru' : undefined
}
const titleLang = computed(() => langOf(props.item.title))
const descLang = computed(() => langOf(props.item.description))
</script>

<template>
  <article class="surface group relative flex flex-col gap-2.5 p-4 transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)]" data-testid="imported-card">
    <div class="flex items-start gap-3">
      <ImportedAvatar :type="item.type" />
      <div class="min-w-0 flex-1">
        <p v-if="place" class="flex items-center gap-1 text-sm font-semibold text-default">
          <UIcon name="i-lucide-map-pin" class="size-3.5 shrink-0 text-muted" aria-hidden="true" />
          <span class="truncate">{{ place }}</span>
          <span v-if="item.is_urgent" class="ml-auto inline-flex shrink-0 items-center gap-1 rounded-full bg-apricot-100 px-2 py-0.5 text-xs font-bold text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200">
            <UIcon name="i-lucide-zap" class="size-3" aria-hidden="true" />{{ t('vacancy.urgent') }}
          </span>
        </p>
        <h3 class="line-clamp-2 font-display text-base font-extrabold leading-snug text-highlighted" :lang="titleLang">
          <NuxtLink :to="localePath(`/elon/${item.id}`)" class="after:absolute after:inset-0 after:rounded-3xl focus-visible:outline-2 focus-visible:outline-primary">
            {{ title }}
          </NuxtLink>
        </h3>
      </div>
    </div>

    <p v-if="hasPrice" class="font-display text-xl font-black tabular-nums text-highlighted"><AppText :text="salary(item.price)" /></p>

    <p v-if="item.care_location" class="inline-flex w-fit items-center gap-1 rounded-full border border-default px-2.5 py-0.5 text-xs font-medium text-default">
      <UIcon name="i-lucide-house" class="size-3.5 text-muted" aria-hidden="true" />{{ enumLabel('careLocation', item.care_location) }}
    </p>

    <p v-if="item.description" class="line-clamp-2 text-sm leading-relaxed text-default" :lang="descLang">{{ item.description }}</p>

    <div class="mt-auto flex items-center justify-between gap-2 border-t border-default pt-2.5 text-xs text-muted">
      <span class="inline-flex min-w-0 items-center gap-1.5">
        <UIcon name="i-lucide-send" class="size-3.5 shrink-0 text-brand-500" aria-hidden="true" />
        <span class="truncate">@{{ item.source.channel }} · {{ relativeTime(item.source.posted_at || item.published_at) }}</span>
      </span>
      <span class="shrink-0 rounded-full bg-sand-100 px-2 py-0.5 font-semibold text-sand-700 dark:bg-sand-800 dark:text-sand-200">{{ t('imported.badge.unverified') }}</span>
    </div>
  </article>
</template>
