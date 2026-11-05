<script setup lang="ts">
import type { Region } from '~/composables/useApi'

defineProps<{ regions: Region[] }>()
const { t } = useI18n()
const localizedName = useLocalizedName()
</script>

<template>
  <section class="py-10 sm:py-12">
    <UContainer>
      <p class="eyebrow text-center text-primary">{{ t('home.regions.title') }}</p>
      <ul class="mx-auto mt-4 flex max-w-4xl flex-wrap justify-center gap-2">
        <li v-for="r in regions" :key="r.id">
          <span class="inline-flex items-center gap-1 rounded-full border border-default bg-default px-3 py-1 text-sm font-medium text-default dark:bg-sand-900">
            <UIcon name="i-lucide-map-pin" class="size-3.5 text-primary" aria-hidden="true" />
            {{ localizedName(r.name) }}
          </span>
        </li>
      </ul>
    </UContainer>
  </section>
</template>
