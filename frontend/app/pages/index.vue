<script setup lang="ts">
import type { Badge, Region } from '~/composables/useApi'

const { t } = useI18n()
const api = useApi()

useSeoMeta({
  title: () => t('home.seoTitle'),
  description: () => t('home.seoDescription'),
  ogTitle: () => t('home.seoTitle'),
  ogDescription: () => t('home.seoDescription'),
})

const [{ data: regions }, { data: badges }] = await Promise.all([
  useAsyncData('regions', () => api<{ data: Region[] }>('/regions').then(r => r.data), { default: () => [] }),
  useAsyncData('badges', () => api<{ data: Badge[] }>('/badges').then(r => r.data), { default: () => [] }),
])
</script>

<template>
  <div>
    <HomeHero :regions="regions" />
    <HomeAudiences />
    <HomeHow />
    <HomeTrust :badges="badges" />
    <HomeAi />
    <HomeRegions :regions="regions" />
    <HomeFaq />
    <HomeCta />
  </div>
</template>
