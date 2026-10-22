<script setup lang="ts">
import type { Region } from '~/composables/useApi'

const props = defineProps<{ regions: Region[] }>()
const { t } = useI18n()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const auth = useAuthStore()

type Mode = 'nanny' | 'job'
const mode = ref<Mode>('nanny')
const regionId = ref<number | 'all'>('all')

const items = computed(() => [
  { label: t('home.hero.allRegions'), value: 'all' as const },
  ...props.regions.map(r => ({ label: localizedName(r.name), value: r.id })),
])

const tabs = computed(() => [
  { value: 'nanny' as const, label: t('home.hero.tabNanny'), icon: 'i-lucide-baby' },
  { value: 'job' as const, label: t('home.hero.tabJob'), icon: 'i-lucide-briefcase' },
])

const chips = computed(() => [
  { icon: 'i-lucide-badge-check', label: t('home.hero.chips.verified') },
  { icon: 'i-lucide-gift', label: t('home.hero.chips.free') },
  { icon: 'i-lucide-map-pin', label: t('home.hero.chips.regions') },
])

// Qidiruv sahifalari keyingi bosqichda; hozircha kirish yoki kabinetga yoʻnaltiramiz.
function search() {
  navigateTo(localePath(auth.isLoggedIn ? '/kabinet' : '/kirish'))
}
</script>

<template>
  <section class="relative overflow-hidden bg-linear-to-b from-brand-50 to-default dark:from-brand-950 dark:to-default">
    <UContainer class="grid items-center gap-10 py-12 sm:py-16 lg:grid-cols-[1.1fr_0.9fr] lg:gap-8 lg:py-24">
      <div>
        <p class="eyebrow inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/70 px-3 py-1.5 text-brand-700 dark:border-brand-800 dark:bg-brand-900/60 dark:text-brand-300">
          <span class="size-1.5 rounded-full bg-apricot-500 dark:bg-apricot-300" aria-hidden="true" />
          {{ t('home.hero.eyebrow') }}
        </p>

        <h1 class="display-title mt-5 text-highlighted">
          {{ t('home.hero.title1') }}
          <span class="text-primary">{{ t('home.hero.title2') }}</span>
        </h1>
        <p class="mt-5 max-w-xl text-lg leading-relaxed text-muted sm:text-xl">
          {{ t('home.hero.subtitle') }}
        </p>

        <form class="surface mt-8 max-w-xl p-3 sm:p-4" @submit.prevent="search">
          <div role="group" :aria-label="t('home.hero.tabsLabel')" class="grid grid-cols-2 gap-1 rounded-xl bg-elevated p-1 dark:bg-sand-800">
            <button
              v-for="tab in tabs"
              :key="tab.value"
              type="button"
              :aria-pressed="mode === tab.value"
              class="flex items-center justify-center gap-2 rounded-[10px] px-2 py-2.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-primary sm:text-base"
              :class="mode === tab.value ? 'bg-default text-primary shadow-sm dark:bg-sand-950' : 'text-muted hover:text-highlighted'"
              @click="mode = tab.value"
            >
              <UIcon :name="tab.icon" class="size-4.5 shrink-0" aria-hidden="true" />
              <span>{{ tab.label }}</span>
            </button>
          </div>

          <div class="mt-3 flex flex-col gap-3 sm:flex-row">
            <USelect
              v-model="regionId"
              :items="items"
              icon="i-lucide-map-pin"
              size="xl"
              class="w-full sm:flex-1"
              :aria-label="t('home.hero.region')"
              :placeholder="t('home.hero.regionPlaceholder')"
            />
            <UButton
              type="submit"
              size="xl"
              class="justify-center sm:px-6"
              :icon="mode === 'nanny' ? 'i-lucide-search' : 'i-lucide-briefcase'"
              :label="mode === 'nanny' ? t('home.hero.ctaNanny') : t('home.hero.ctaJob')"
            />
          </div>
        </form>

        <ul class="mt-6 flex flex-wrap gap-x-5 gap-y-2.5 text-sm font-medium text-default">
          <li v-for="c in chips" :key="c.label" class="inline-flex items-center gap-2">
            <span class="grid size-6 place-items-center rounded-full bg-leaf-100 text-leaf-700 dark:bg-leaf-950 dark:text-leaf-400">
              <UIcon :name="c.icon" class="size-3.5" aria-hidden="true" />
            </span>
            {{ c.label }}
          </li>
        </ul>
      </div>

      <HomeHeroVisual />
    </UContainer>
  </section>
</template>
