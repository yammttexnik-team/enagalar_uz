<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
const year = new Date().getFullYear()

const platform = computed(() => [
  { label: t('nav.nannies'), to: localePath({ path: '/', hash: '#parents' }) },
  { label: t('nav.vacancies'), to: localePath({ path: '/', hash: '#nannies' }) },
  { label: t('nav.how'), to: localePath({ path: '/', hash: '#how' }) },
])
const safety = computed(() => [
  { label: t('footer.trust'), to: localePath({ path: '/', hash: '#trust' }) },
  { label: t('footer.faq'), to: localePath({ path: '/', hash: '#faq' }) },
])
const telegram = computed(() => [
  { label: t('footer.channel'), to: 'https://t.me/enagalar_uz', icon: 'i-lucide-send' },
  { label: t('footer.bot'), to: 'https://t.me/enagalar_uz_bot', icon: 'i-lucide-bot' },
])
</script>

<template>
  <footer class="relative overflow-hidden bg-brand-950 text-sand-200">
    <UContainer class="py-14">
      <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
          <img src="/brand/logo-dark.svg" alt="enagalar.uz" width="152" height="30" class="h-[30px] w-auto">
          <p class="mt-4 max-w-sm text-sm leading-relaxed text-sand-300">
            {{ t('footer.tagline') }}
          </p>
        </div>

        <div>
          <h2 class="eyebrow text-brand-300">{{ t('footer.platform') }}</h2>
          <ul class="mt-4 space-y-2.5 text-sm">
            <li v-for="l in platform" :key="l.label">
              <NuxtLink :to="l.to" class="text-sand-200 hover:text-white underline-offset-4 hover:underline">{{ l.label }}</NuxtLink>
            </li>
          </ul>
        </div>

        <div>
          <h2 class="eyebrow text-brand-300">{{ t('footer.safety') }}</h2>
          <ul class="mt-4 space-y-2.5 text-sm">
            <li v-for="l in safety" :key="l.label">
              <NuxtLink :to="l.to" class="text-sand-200 hover:text-white underline-offset-4 hover:underline">{{ l.label }}</NuxtLink>
            </li>
          </ul>
        </div>

        <div>
          <h2 class="eyebrow text-brand-300">{{ t('footer.telegram') }}</h2>
          <ul class="mt-4 space-y-2.5 text-sm">
            <li v-for="l in telegram" :key="l.label">
              <a :href="l.to" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sand-200 hover:text-white underline-offset-4 hover:underline">
                <UIcon :name="l.icon" class="size-4" aria-hidden="true" />{{ l.label }}
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-12 flex flex-col gap-3 border-t border-white/10 pt-6 text-sm text-sand-300 sm:flex-row sm:items-center sm:justify-between">
        <p>© {{ year }} enagalar.uz. {{ t('footer.rights') }}</p>
        <p class="inline-flex items-center gap-2">
          <UIcon name="i-lucide-map-pin" class="size-4 text-apricot-300" aria-hidden="true" />
          {{ t('footer.everywhere') }}
        </p>
      </div>
    </UContainer>
  </footer>
</template>
