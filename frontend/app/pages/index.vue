<script setup lang="ts">
import type { Region } from '~/composables/useApi'

const { t } = useI18n()
const api = useApi()
const localizedName = useLocalizedName()

useSeoMeta({
  title: () => t('home.seoTitle'),
  description: () => t('home.subtitle'),
})

const { data: regions } = await useAsyncData('regions', () =>
  api<{ data: Region[] }>('/regions').then(r => r.data),
)

const regionId = ref<number>()
const regionItems = computed(() =>
  (regions.value ?? []).map(r => ({ label: localizedName(r.name), value: r.id })),
)

const features = computed(() => [
  { icon: 'i-lucide-badge-check', title: t('home.features.verified.title'), text: t('home.features.verified.text') },
  { icon: 'i-lucide-messages-square', title: t('home.features.chat.title'), text: t('home.features.chat.text') },
  { icon: 'i-lucide-sparkles', title: t('home.features.ai.title'), text: t('home.features.ai.text') },
  { icon: 'i-lucide-gift', title: t('home.features.free.title'), text: t('home.features.free.text') },
])
</script>

<template>
  <div>
    <section class="py-16 sm:py-24 bg-primary-50 dark:bg-primary-950/30">
      <UContainer class="text-center max-w-3xl">
        <h1 class="text-3xl sm:text-5xl font-bold tracking-tight">
          {{ $t('home.title') }}
        </h1>
        <p class="mt-4 text-lg text-muted">
          {{ $t('home.subtitle') }}
        </p>

        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
          <USelect
            v-model="regionId"
            :items="regionItems"
            :placeholder="$t('home.chooseRegion')"
            class="sm:w-64"
            size="xl"
          />
          <UButton size="xl" icon="i-lucide-search" :label="$t('home.findNanny')" />
        </div>
        <UButton class="mt-4" variant="link" :label="$t('home.iAmNanny')" />
      </UContainer>
    </section>

    <section class="py-16">
      <UContainer class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <UCard v-for="feature in features" :key="feature.title">
          <UIcon :name="feature.icon" class="size-8 text-primary" />
          <h3 class="mt-3 font-semibold">
            {{ feature.title }}
          </h3>
          <p class="mt-1 text-sm text-muted">
            {{ feature.text }}
          </p>
        </UCard>
      </UContainer>
    </section>
  </div>
</template>
