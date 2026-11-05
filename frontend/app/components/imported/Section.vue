<script setup lang="ts">
import type { ImportedItem } from '~/components/imported/Card.vue'

interface Paginated<T> { data: T[], meta: { total: number, current_page: number, last_page: number } }

/** Qidiruv natijalaridan keyin: Telegram kanallardan olingan e'lonlar (shu filtrlar bilan). */
const props = defineProps<{ type: 'vacancy' | 'nanny', filters: Record<string, unknown>, primary?: boolean, promoTo?: string }>()
const authLink = useAuthLink()
const { t } = useI18n()
const api = useApi()

const query = computed(() => ({
  type: props.type,
  region: props.filters.region || undefined,
  district: props.filters.district || undefined,
  work_format: props.filters.work_format || undefined,
  care_location: props.filters.care_location || undefined,
}))

const page = ref(1)
const extra = ref<ImportedItem[]>([])
const loadingMore = ref(false)

const { data } = await useAsyncData(
  `imported-${props.type}`,
  () => api<Paginated<ImportedItem>>('/imported', { query: query.value }).catch(() => null),
  { watch: [query] },
)
watch(query, () => { page.value = 1; extra.value = [] })

const items = computed(() => [...(data.value?.data ?? []), ...extra.value])
const hasMore = computed(() => !!data.value && page.value < data.value.meta.last_page)

async function more() {
  loadingMore.value = true
  try {
    const res = await api<Paginated<ImportedItem>>('/imported', { query: { ...query.value, page: page.value + 1 } })
    extra.value.push(...res.data)
    page.value = res.meta.current_page
  }
  catch { /* keyingi bosishda qayta urinadi */ }
  finally { loadingMore.value = false }
}
</script>

<template>
  <section v-if="items.length" :class="primary ? 'mt-0' : 'mt-12'" :aria-labelledby="`imported-${type}-title`" data-testid="imported-section">
    <div v-if="!primary" class="mb-4 flex flex-wrap items-end justify-between gap-2">
      <div>
        <h2 :id="`imported-${type}-title`" class="h3-title flex items-center gap-2 text-highlighted">
          <UIcon name="i-lucide-send" class="size-5 text-brand-500" aria-hidden="true" />{{ t('imported.section.title') }}
        </h2>
        <p class="mt-1 max-w-2xl text-sm text-muted">{{ t(`imported.section.text.${type}`) }}</p>
      </div>
      <span class="text-sm text-muted">{{ t('imported.section.count', { n: data?.meta.total ?? items.length }) }}</span>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
      <template v-for="(i, idx) in items" :key="i.id">
        <ImportedCard :item="i" />
        <NuxtLink
          v-if="primary && promoTo && idx === Math.min(1, items.length - 1)"
          :to="authLink(promoTo)"
          class="flex items-center gap-3 rounded-3xl border border-dashed border-brand-300 bg-brand-50 p-4 transition hover:bg-brand-100 focus-visible:outline-2 focus-visible:outline-primary dark:border-brand-700 dark:bg-brand-950 dark:hover:bg-brand-900 sm:col-span-2"
          data-testid="imported-promo"
        >
          <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-600 text-white dark:bg-brand-300 dark:text-brand-950">
            <UIcon name="i-lucide-gift" class="size-5.5" aria-hidden="true" />
          </span>
          <span class="min-w-0 flex-1 font-semibold leading-snug text-highlighted">{{ t(`imported.list.promo.${type}`) }}</span>
          <UIcon name="i-lucide-arrow-right" class="size-5 shrink-0 text-brand-600 dark:text-brand-300" aria-hidden="true" />
        </NuxtLink>
      </template>
    </div>
    <div v-if="hasMore" class="mt-6 flex justify-center">
      <UButton color="neutral" variant="outline" icon="i-lucide-chevrons-down" :loading="loadingMore" :label="t('imported.section.more')" @click="more" />
    </div>
  </section>
</template>
