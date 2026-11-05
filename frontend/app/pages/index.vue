<script setup lang="ts">
import type { Badge, Region } from '~/composables/useApi'
import type { ImportedItem } from '~/components/imported/Card.vue'

const { t } = useI18n()
const api = useApi()

useSeoMeta({
  title: () => t('home.seoTitle'),
  description: () => t('home.seoDescription'),
  ogTitle: () => t('home.seoTitle'),
  ogDescription: () => t('home.seoDescription'),
})

interface ImportedList { data: ImportedItem[], meta: { total: number } }
const latest = (type: 'nanny' | 'vacancy') => api<ImportedList>('/imported', { query: { type } }).catch(() => null)

const [{ data: regions }, { data: badges }, { data: nannyList }, { data: vacancyList }] = await Promise.all([
  useAsyncData('regions', () => api<{ data: Region[] }>('/regions').then(r => r.data), { default: () => [] }),
  useAsyncData('badges', () => api<{ data: Badge[] }>('/badges').then(r => r.data), { default: () => [] }),
  useAsyncData('home-imported-nanny', () => latest('nanny'), { default: () => null }),
  useAsyncData('home-imported-vacancy', () => latest('vacancy'), { default: () => null }),
])
</script>

<template>
  <div>
    <HomeHero
      :regions="regions"
      :nanny="nannyList?.data[0] ?? null"
      :vacancy="vacancyList?.data[0] ?? null"
      :total="(nannyList?.meta.total ?? 0) + (vacancyList?.meta.total ?? 0)"
    />
    <HomeHow />
    <HomeTrust :badges="badges" />
    <HomeAi />
    <HomeRegions :regions="regions" />
    <HomeFaq />
    <HomeCta />
  </div>
</template>
