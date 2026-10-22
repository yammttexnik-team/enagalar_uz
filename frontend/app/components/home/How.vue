<script setup lang="ts">
const { t, tm, rt } = useI18n()
const icons = ['i-lucide-send', 'i-lucide-file-pen-line', 'i-lucide-sparkles', 'i-lucide-messages-square']
const steps = computed(() =>
  (tm('home.how.steps') as { title: unknown, text: unknown }[]).map((s, i) => ({
    title: rt(s.title as never),
    text: rt(s.text as never),
    icon: icons[i],
  })),
)
</script>

<template>
  <section id="how" class="bg-sand-100 py-16 dark:bg-sand-900/60 sm:py-24">
    <UContainer>
      <HomeSectionHead center :eyebrow="t('home.how.eyebrow')" :title="t('home.how.title')" :subtitle="t('home.how.subtitle')" />

      <ol class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <li v-for="(s, i) in steps" :key="i" class="surface relative p-6">
          <div class="flex items-center justify-between">
            <span class="grid size-12 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
              <UIcon :name="s.icon!" class="size-6" aria-hidden="true" />
            </span>
            <span class="font-display text-5xl font-black leading-none text-sand-200 dark:text-sand-700" aria-hidden="true">{{ i + 1 }}</span>
          </div>
          <h3 class="mt-5 font-display text-lg font-extrabold leading-snug text-highlighted"><AppText :text="s.title" /></h3>
          <p class="mt-2 text-[15px] leading-relaxed text-muted">{{ s.text }}</p>
        </li>
      </ol>
    </UContainer>
  </section>
</template>
