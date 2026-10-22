<script setup lang="ts">
const { t, tm, rt } = useI18n()
const localePath = useLocalePath()
const authLink = useAuthLink()

const cards = computed(() => [
  {
    id: 'parents',
    key: 'parents',
    icon: 'i-lucide-house-heart',
    tone: 'brand',
    cta: { color: 'primary' as const, icon: 'i-lucide-megaphone', to: '/kabinet/vakansiyalar/yangi' },
    secondary: '/enagalar',
  },
  {
    id: 'nannies',
    key: 'nannies',
    icon: 'i-lucide-heart-handshake',
    tone: 'apricot',
    cta: { color: 'secondary' as const, icon: 'i-lucide-file-pen-line', to: '/kabinet/anketa' },
    secondary: '/vakansiyalar',
  },
])
const points = (key: string) => (tm(`home.audiences.${key}.points`) as unknown[]).map(p => rt(p as never))
</script>

<template>
  <section class="py-16 sm:py-24">
    <UContainer>
      <HomeSectionHead
        center
        :eyebrow="t('home.audiences.eyebrow')"
        :title="t('home.audiences.title')"
        :subtitle="t('home.audiences.subtitle')"
      />

      <div class="mt-12 grid gap-6 lg:grid-cols-2">
        <article
          v-for="c in cards"
          :id="c.id"
          :key="c.id"
          class="surface flex flex-col p-6 sm:p-8"
          :class="c.tone === 'apricot' ? 'bg-apricot-50! dark:bg-apricot-950/40!' : ''"
        >
          <div class="flex items-center gap-3">
            <span
              class="grid size-12 place-items-center rounded-2xl"
              :class="c.tone === 'apricot' ? 'bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200' : 'bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200'"
            >
              <UIcon :name="c.icon" class="size-6" aria-hidden="true" />
            </span>
            <p class="eyebrow" :class="c.tone === 'apricot' ? 'text-apricot-700 dark:text-apricot-300' : 'text-brand-700 dark:text-brand-300'">
              {{ t(`home.audiences.${c.key}.label`) }}
            </p>
          </div>
          <h3 class="h2-title mt-5 text-highlighted"><AppText :text="t(`home.audiences.${c.key}.title`)" /></h3>
          <p class="mt-3 text-base leading-relaxed text-muted">{{ t(`home.audiences.${c.key}.text`) }}</p>

          <ul class="mt-6 space-y-3">
            <li v-for="p in points(c.key)" :key="p" class="flex items-start gap-3 text-[15px] text-default">
              <UIcon name="i-lucide-circle-check" class="mt-0.5 size-5 shrink-0 text-leaf-600 dark:text-leaf-400" aria-hidden="true" />
              <span>{{ p }}</span>
            </li>
          </ul>

          <div class="mt-8 flex flex-col gap-3 pt-2 sm:flex-row sm:flex-wrap">
            <UButton size="xl" :color="c.cta.color" :icon="c.cta.icon" :to="authLink(c.cta.to)" :label="t(`home.audiences.${c.key}.cta`)" class="justify-center" />
            <UButton size="xl" color="neutral" variant="outline" :to="localePath(c.secondary)" :label="t(`home.audiences.${c.key}.ctaSecondary`)" class="justify-center" />
          </div>
        </article>
      </div>
    </UContainer>
  </section>
</template>
