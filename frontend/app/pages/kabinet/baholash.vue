<script setup lang="ts">
import type { ConnectionItem } from '~/composables/useRating'

const { t } = useI18n()
const { list } = useConnections()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('rating.seo'), robots: 'noindex' })

const { data, status, error, refresh } = await useAsyncData('my-connections', () => list().then(r => r.data), { default: () => [] as ConnectionItem[] })

// Avval javob/baho kutilayotganlar, keyin qolganlari
const ORDER: Record<ConnectionItem['status'], number> = { question: 0, can_review: 1, waiting: 2, reviewed: 3, not_worked: 4 }
const items = computed(() => [...data.value].sort((a, b) => ORDER[a.status] - ORDER[b.status]))

function replace(item: ConnectionItem) {
  data.value = data.value.map(x => (x.id === item.id ? item : x))
}
</script>

<template>
  <div>
    <DashboardPageHead :title="t('rating.title')" :subtitle="t('rating.subtitle')" />

    <SearchSkeleton v-if="status === 'pending'" />
    <div v-else-if="error" class="surface px-6 py-10 text-center" role="alert">
      <p class="text-muted">{{ t('dashboard.common.error') }}</p>
      <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="refresh()" />
    </div>
    <div v-else-if="items.length" class="space-y-4">
      <RatingConnectionCard v-for="c in items" :key="c.id" :item="c" @update="replace" />
    </div>
    <div v-else class="surface flex flex-col items-center px-6 py-12 text-center">
      <span class="grid size-14 place-items-center rounded-full bg-honey-100 text-honey-600 dark:bg-honey-900 dark:text-honey-300"><UIcon name="i-lucide-star" class="size-7" aria-hidden="true" /></span>
      <p class="mt-4 max-w-md text-muted">{{ t('rating.empty') }}</p>
    </div>
  </div>
</template>
