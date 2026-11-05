<script setup lang="ts">
import type { ImportedItem } from '~/components/imported/Card.vue'

const props = defineProps<{ item: ImportedItem, kind: 'nanny' | 'vacancy' }>()
const { t } = useI18n()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { salary, relativeTime } = useFormat()

const title = computed(() => props.item.title || t(`imported.fallbackTitle.${props.item.type}`))
const place = computed(() => [props.item.district, props.item.region].filter(Boolean).map(p => localizedName(p!.name)).slice(0, 2).join(', '))
const hasPrice = computed(() => !!(props.item.price.min || props.item.price.max))
</script>

<template>
  <article class="surface relative p-4 transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)]" style="box-shadow: var(--shadow-soft)">
    <p class="eyebrow inline-flex items-center gap-1.5" :class="kind === 'nanny' ? 'text-brand-700 dark:text-brand-300' : 'text-apricot-700 dark:text-apricot-300'">
      <UIcon :name="kind === 'nanny' ? 'i-lucide-baby' : 'i-lucide-briefcase'" class="size-3.5" aria-hidden="true" />
      {{ t(`home.hero.visual.${kind}`) }}
    </p>
    <h3 class="mt-1.5 line-clamp-2 font-display text-base font-extrabold leading-snug text-highlighted">
      <NuxtLink :to="localePath(`/elon/${item.id}`)" class="after:absolute after:inset-0 after:rounded-3xl focus-visible:outline-2 focus-visible:outline-primary">{{ title }}</NuxtLink>
    </h3>
    <div class="mt-1 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 text-sm">
      <p v-if="place" class="flex min-w-0 items-center gap-1 text-muted">
        <UIcon name="i-lucide-map-pin" class="size-3.5 shrink-0" aria-hidden="true" /><span class="truncate">{{ place }}</span>
      </p>
      <p v-if="hasPrice" class="font-display font-black tabular-nums text-highlighted"><AppText :text="salary(item.price)" /></p>
    </div>
    <p class="mt-2.5 flex items-center justify-between gap-2 border-t border-default pt-2 text-xs text-muted">
      <span class="truncate">@{{ item.source.channel }}</span>
      <span class="shrink-0">{{ relativeTime(item.source.posted_at || item.published_at) }}</span>
    </p>
  </article>
</template>
