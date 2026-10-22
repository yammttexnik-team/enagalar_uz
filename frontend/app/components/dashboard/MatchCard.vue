<script setup lang="ts">
import type { NannyItem } from '~/components/nanny/Card.vue'
import type { VacancyItem } from '~/components/vacancy/Card.vue'

const props = defineProps<{ match: MatchItem<NannyItem | VacancyItem>, kind: 'nanny' | 'vacancy', rank?: number }>()
const { t, locale } = useI18n()

const item = computed(() => (props.kind === 'nanny' ? props.match.nanny : props.match.vacancy)!)
const reason = computed(() => (locale.value === 'ru' ? props.match.reason.ru || props.match.reason.uz : props.match.reason.uz || props.match.reason.ru))
</script>

<template>
  <div class="surface overflow-hidden p-0!">
    <div class="flex items-center gap-4 border-b border-default bg-brand-50/70 px-5 py-4 dark:bg-brand-950/50">
      <DashboardScoreRing :score="match.score" />
      <div class="min-w-0 flex-1">
        <p class="font-display text-base font-extrabold text-highlighted">{{ t('dashboard.match.score', { n: match.score }) }}</p>
        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-sand-200 dark:bg-sand-800" aria-hidden="true">
          <div class="h-full rounded-full bg-brand-500 dark:bg-brand-400" :style="{ width: `${Math.min(100, match.score)}%` }" />
        </div>
      </div>
      <UBadge v-if="match.source === 'ai'" color="primary" variant="solid" icon="i-lucide-sparkles" :label="t('dashboard.match.ai')" />
      <UBadge v-else color="neutral" variant="subtle" icon="i-lucide-list-checks" :label="t('dashboard.match.rules')" />
    </div>
    <p v-if="reason" class="flex items-start gap-2 px-5 pt-4 text-[15px] text-default">
      <UIcon name="i-lucide-message-square-quote" class="mt-0.5 size-4.5 shrink-0 text-primary" aria-hidden="true" />
      <span><span class="font-semibold text-highlighted">{{ t('dashboard.match.why') }}:</span> {{ reason }}</span>
    </p>
    <div class="p-3">
      <NannyCard v-if="kind === 'nanny'" :nanny="(item as NannyItem)" class="border-0! shadow-none!" />
      <VacancyCard v-else :vacancy="(item as VacancyItem)" class="border-0! shadow-none!" />
    </div>
  </div>
</template>
