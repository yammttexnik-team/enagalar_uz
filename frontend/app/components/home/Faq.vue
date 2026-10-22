<script setup lang="ts">
const { t, tm, rt } = useI18n()
const items = computed(() =>
  (tm('home.faq.items') as { label: unknown, content: unknown }[]).map((i, n) => ({
    label: rt(i.label as never),
    content: rt(i.content as never),
    value: String(n),
  })),
)
</script>

<template>
  <section id="faq" class="bg-sand-100 py-16 dark:bg-sand-900/60 sm:py-24">
    <UContainer class="max-w-3xl">
      <HomeSectionHead center :eyebrow="t('home.faq.eyebrow')" :title="t('home.faq.title')" />
      <div class="surface mt-10 px-5 py-2 sm:px-8">
        <UAccordion :items="items" type="single" collapsible :ui="{ item: 'border-default', content: 'text-muted text-base leading-relaxed', trailingIcon: 'text-primary' }" />
      </div>
    </UContainer>
  </section>
</template>
