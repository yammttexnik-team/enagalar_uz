<script setup lang="ts">
const props = defineProps<{
  title: string
  subtitle: string
  countLabel: string
  pending: boolean
  sortItems: { label: string, value: string }[]
  sort: string
  activeCount: number
  total: number
}>()
const emit = defineEmits<{ 'update:sort': [value: string], reset: [] }>()
const { t } = useI18n()
const sheet = ref(false)
const sortModel = computed({ get: () => props.sort, set: v => emit('update:sort', v) })
</script>

<template>
  <div>
    <section class="bg-linear-to-b from-brand-50 to-default py-8 dark:from-brand-950 dark:to-default sm:py-12">
      <UContainer>
        <h1 class="h1-title text-highlighted"><AppText :text="title" /></h1>
        <p class="mt-2 max-w-2xl text-base text-muted sm:text-lg">{{ subtitle }}</p>
      </UContainer>
    </section>

    <UContainer class="grid gap-8 pb-16 pt-6 lg:grid-cols-[300px_minmax(0,1fr)] lg:pt-8">
      <aside class="hidden lg:block" :aria-label="t('search.filters')">
        <div class="surface sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto p-5">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('search.filters') }}</h2>
            <UButton v-if="activeCount" size="xs" color="neutral" variant="link" :label="t('search.reset')" @click="emit('reset')" />
          </div>
          <slot name="filters" />
        </div>
      </aside>

      <section id="results" class="min-w-0 scroll-mt-20">
        <div class="mb-4 flex flex-wrap items-center gap-3">
          <p class="mr-auto text-[15px] font-semibold text-highlighted" role="status" aria-live="polite">
            <span v-if="pending" class="text-muted">{{ t('search.loading') }}</span>
            <span v-else>{{ countLabel }}</span>
          </p>
          <UButton
            class="lg:hidden"
            color="neutral"
            variant="outline"
            icon="i-lucide-sliders-horizontal"
            :label="activeCount ? `${t('search.filters')} · ${activeCount}` : t('search.filters')"
            @click="sheet = true"
          />
          <USelect
            v-model="sortModel"
            :items="sortItems"
            icon="i-lucide-arrow-up-down"
            class="w-52"
            :aria-label="t('search.sort')"
          />
        </div>
        <slot />
      </section>
    </UContainer>

    <USlideover v-model:open="sheet" side="left" :title="t('search.filters')" :close="{ 'aria-label': t('search.close') }">
      <template #body>
        <slot name="filters" />
      </template>
      <template #footer>
        <div class="flex w-full gap-2">
          <UButton color="neutral" variant="outline" size="lg" class="justify-center" :label="t('search.reset')" @click="emit('reset')" />
          <UButton size="lg" block class="justify-center" :label="`${t('search.showResults')} (${total})`" @click="sheet = false" />
        </div>
      </template>
    </USlideover>
  </div>
</template>
