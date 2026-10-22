<script setup lang="ts">
import type { Badge } from '~/composables/useApi'

defineProps<{ badges: Badge[] }>()
const { t, te } = useI18n()
const localizedName = useLocalizedName()

const tones: Record<string, string> = {
  success: 'bg-leaf-50 text-leaf-700 border-leaf-200 dark:bg-leaf-950 dark:text-leaf-300 dark:border-leaf-800',
  info: 'bg-azure-50 text-azure-700 border-azure-200 dark:bg-azure-950 dark:text-azure-300 dark:border-azure-800',
  primary: 'bg-brand-50 text-brand-700 border-brand-200 dark:bg-brand-950 dark:text-brand-300 dark:border-brand-800',
  warning: 'bg-honey-50 text-honey-800 border-honey-200 dark:bg-honey-950 dark:text-honey-300 dark:border-honey-800',
  neutral: 'bg-sand-100 text-sand-700 border-sand-200 dark:bg-sand-800 dark:text-sand-200 dark:border-sand-700',
}
const description = (b: Badge) =>
  (b.description ? localizedName(b.description as never) : '') || (te(`home.trust.badgeText.${b.slug}`) ? t(`home.trust.badgeText.${b.slug}`) : '')
const tone = (c: string) => tones[c] ?? tones.neutral

const facts = computed(() => [
  { icon: 'i-lucide-id-card', key: 'passport' },
  { icon: 'i-lucide-trash-2', key: 'deleted' },
  { icon: 'i-lucide-lock-keyhole', key: 'private' },
])
</script>

<template>
  <section id="trust" class="py-16 sm:py-24">
    <UContainer>
      <div class="grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
        <div>
          <HomeSectionHead :eyebrow="t('home.trust.eyebrow')" :title="t('home.trust.title')" :subtitle="t('home.trust.subtitle')" />
          <ul class="mt-8 space-y-5">
            <li v-for="f in facts" :key="f.key" class="flex items-start gap-4">
              <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                <UIcon :name="f.icon" class="size-5.5" aria-hidden="true" />
              </span>
              <div>
                <h3 class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t(`home.trust.${f.key}.title`)" /></h3>
                <p class="mt-1 text-[15px] leading-relaxed text-muted">{{ t(`home.trust.${f.key}.text`) }}</p>
              </div>
            </li>
          </ul>
        </div>

        <div>
          <h3 class="eyebrow text-muted">{{ t('home.trust.badgesTitle') }}</h3>
          <ul class="mt-4 grid gap-3 sm:grid-cols-2">
            <li
              v-for="b in badges"
              :key="b.slug"
              class="surface flex items-start gap-3 rounded-2xl! p-4"
            >
              <span class="grid size-10 shrink-0 place-items-center rounded-full border" :class="tone(b.color)">
                <UIcon :name="b.icon" class="size-5" aria-hidden="true" />
              </span>
              <div class="min-w-0">
                <p class="font-semibold leading-snug text-highlighted">{{ localizedName(b.name) }}</p>
                <p v-if="description(b)" class="mt-1 text-sm leading-snug text-muted">{{ description(b) }}</p>
              </div>
            </li>
          </ul>

          <div class="mt-5 flex items-start gap-3 rounded-2xl border border-honey-200 bg-honey-50 p-4 text-sm text-honey-800 dark:border-honey-800 dark:bg-honey-950 dark:text-honey-200">
            <UIcon name="i-lucide-triangle-alert" class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
            <p>{{ t('home.trust.tip') }}</p>
          </div>
        </div>
      </div>
    </UContainer>
  </section>
</template>
