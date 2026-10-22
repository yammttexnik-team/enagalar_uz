<script setup lang="ts">
const { t, tm, rt } = useI18n()
const localePath = useLocalePath()
const icons = ['i-lucide-search-check', 'i-lucide-pen-line', 'i-lucide-languages']
const items = computed(() =>
  (tm('home.ai.items') as { title: unknown, text: unknown }[]).map((s, i) => ({
    title: rt(s.title as never),
    text: rt(s.text as never),
    icon: icons[i]!,
  })),
)
</script>

<template>
  <section class="px-4 py-4 sm:px-6 sm:py-8">
    <div class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-brand-900 px-6 py-14 text-white sm:px-12 sm:py-20 dark:bg-brand-950 dark:ring-1 dark:ring-white/10">
      <svg class="pointer-events-none absolute -right-24 -top-24 size-[420px] opacity-60" viewBox="0 0 100 100" fill="none" aria-hidden="true">
        <path d="M30 14 A38 38 0 1 0 82 66" stroke="#01494f" stroke-width="14" stroke-linecap="round" />
      </svg>

      <div class="relative grid gap-12 lg:grid-cols-[1fr_0.9fr] lg:items-center">
        <div>
          <p class="eyebrow text-brand-300">{{ t('home.ai.eyebrow') }}</p>
          <h2 class="h1-title mt-3 text-white">{{ t('home.ai.title') }}</h2>
          <p class="mt-4 text-lg text-brand-100/90">{{ t('home.ai.subtitle') }}</p>

          <ul class="mt-8 space-y-6">
            <li v-for="it in items" :key="it.title" class="flex items-start gap-4">
              <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white/10 text-brand-200">
                <UIcon :name="it.icon" class="size-5.5" aria-hidden="true" />
              </span>
              <div>
                <h3 class="font-display text-lg font-extrabold"><AppText :text="it.title" /></h3>
                <p class="mt-1 text-[15px] leading-relaxed text-brand-100/85">{{ it.text }}</p>
              </div>
            </li>
          </ul>

          <UButton class="mt-9 justify-center" size="xl" color="secondary" icon="i-lucide-sparkles" :to="localePath('/kirish')" :label="t('home.ai.cta')" />
        </div>

        <div class="space-y-3" aria-hidden="true">
          <div class="ml-auto max-w-[92%] rounded-3xl rounded-br-lg bg-brand-100 px-5 py-4 text-brand-950">
            <p class="eyebrow text-brand-700">{{ t('home.ai.promptLabel') }}</p>
            <p class="mt-1.5 text-[15px] leading-relaxed">{{ t('home.ai.prompt') }}</p>
          </div>
          <div class="max-w-[92%] rounded-3xl rounded-bl-lg bg-white/10 px-5 py-4 ring-1 ring-white/15">
            <p class="eyebrow inline-flex items-center gap-1.5 text-apricot-300">
              <UIcon name="i-lucide-sparkles" class="size-3.5" />{{ t('home.ai.answerLabel') }}
            </p>
            <p class="mt-1.5 text-[15px] leading-relaxed text-white">{{ t('home.ai.answer') }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
