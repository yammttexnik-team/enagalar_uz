<script setup lang="ts">
const { t } = useI18n()
</script>

<template>
  <div
    class="relative mx-auto aspect-[1/1.02] w-full max-w-[480px] select-none lg:ml-auto"
    role="img"
    :aria-label="t('home.hero.visual.alt')"
  >
    <!-- Belgi shakllaridan fon: katta yoy, bosh va bola nuqtasi -->
    <svg class="absolute inset-0 size-full" viewBox="0 0 400 408" aria-hidden="true" fill="none">
      <circle cx="196" cy="214" r="168" class="fill-brand-100 dark:fill-brand-900" />
      <path d="M118 112 A112 112 0 1 0 292 322" stroke-width="44" stroke-linecap="round" class="stroke-brand-200 dark:stroke-brand-800" />
      <circle cx="330" cy="70" r="30" class="fill-brand-400 dark:fill-brand-500" />
      <circle cx="80" cy="330" r="14" class="fill-apricot-300 dark:fill-apricot-400" />
    </svg>

    <!-- Orqadagi ikkinchi anketa -->
    <div class="surface absolute left-[2%] top-[6%] w-[52%] -rotate-3 p-3 opacity-95">
      <div class="flex items-center gap-2.5">
        <HomeAvatar tone="apricot" class="size-10" />
        <div class="min-w-0">
          <p class="truncate text-[13px] font-semibold text-highlighted">{{ t('home.hero.visual.second') }}</p>
          <p class="truncate text-xs text-muted">{{ t('home.hero.visual.secondMeta') }}</p>
        </div>
      </div>
    </div>

    <!-- Asosiy anketa -->
    <div class="surface float-slow absolute right-[2%] top-[20%] w-[70%] p-4" style="box-shadow: var(--shadow-lift)">
      <div class="flex items-center gap-3">
        <HomeAvatar tone="brand" class="size-14" />
        <div class="min-w-0">
          <p class="font-display text-base font-extrabold leading-tight text-highlighted">{{ t('home.hero.visual.name') }}</p>
          <p class="mt-0.5 text-[13px] text-muted">{{ t('home.hero.visual.meta') }}</p>
        </div>
      </div>
      <div class="mt-3.5 flex flex-wrap gap-1.5">
        <UBadge color="success" variant="subtle" icon="i-lucide-badge-check" :label="t('home.hero.visual.verified')" />
        <UBadge color="info" variant="subtle" icon="i-lucide-shield-check" :label="t('home.hero.visual.dispensary')" class="whitespace-normal text-start" />
        <UBadge color="warning" variant="subtle" icon="i-lucide-zap" :label="t('home.hero.visual.quick')" />
      </div>
    </div>

    <!-- Chat -->
    <div class="absolute bottom-[4%] left-[0%] w-[78%] space-y-2">
      <p class="w-[88%] rounded-2xl rounded-bl-md border border-default bg-default px-3.5 py-2.5 text-[13px] leading-snug text-default shadow-[var(--shadow-soft)]">
        {{ t('home.hero.visual.msgIn') }}
      </p>
      <p class="ml-auto w-[76%] rounded-2xl rounded-br-md bg-primary px-3.5 py-2.5 text-[13px] leading-snug text-inverted shadow-[var(--shadow-soft)]">
        {{ t('home.hero.visual.msgOut') }}
      </p>
    </div>
  </div>
</template>
