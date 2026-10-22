<script setup lang="ts">
import type { Region } from '~/composables/useApi'

defineProps<{ regions: Region[] }>()
const { t } = useI18n()
const localizedName = useLocalizedName()
</script>

<template>
  <section class="py-16 sm:py-24">
    <UContainer>
      <HomeSectionHead center :eyebrow="t('home.regions.eyebrow')" :title="t('home.regions.title')" :subtitle="t('home.regions.subtitle')" />
      <ul class="mx-auto mt-10 flex max-w-4xl flex-wrap justify-center gap-2.5">
        <li v-for="r in regions" :key="r.id">
          <span class="inline-flex items-center gap-1.5 rounded-full border border-default bg-default px-4 py-2 text-[15px] font-medium text-default shadow-[var(--shadow-soft)] dark:bg-sand-900">
            <UIcon name="i-lucide-map-pin" class="size-4 text-primary" aria-hidden="true" />
            {{ localizedName(r.name) }}
          </span>
        </li>
      </ul>
    </UContainer>
  </section>
</template>
