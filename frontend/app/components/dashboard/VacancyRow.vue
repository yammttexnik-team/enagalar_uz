<script setup lang="ts">
defineProps<{ vacancy: OwnVacancy }>()
defineEmits<{ updated: [v: OwnVacancy], deleted: [] }>()
const { t } = useI18n()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { salary, enumLabel, date } = useFormat()
</script>

<template>
  <article class="surface flex flex-col gap-4 p-5">
    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
      <div class="min-w-0">
        <div class="mb-1.5 flex flex-wrap items-center gap-2">
          <DashboardStatusBadge kind="vacancy" :status="vacancy.status" />
          <UBadge v-if="vacancy.is_urgent" color="secondary" variant="subtle" icon="i-lucide-zap" :label="t('vacancy.urgent')" />
        </div>
        <h2 class="font-display text-lg font-extrabold leading-snug text-highlighted">
          <NuxtLink :to="localePath(`/kabinet/vakansiyalar/${vacancy.id}`)" class="hover:text-primary focus-visible:outline-2 focus-visible:outline-primary">{{ vacancy.title }}</NuxtLink>
        </h2>
        <p class="mt-1 flex items-center gap-1 text-sm text-muted">
          <UIcon name="i-lucide-map-pin" class="size-3.5 shrink-0" aria-hidden="true" />
          {{ [vacancy.district, vacancy.region].filter(Boolean).map(p => localizedName(p!.name)).join(', ') }}
          · {{ enumLabel('workFormat', vacancy.work_format) }}
        </p>
      </div>
      <p class="font-display text-lg font-black tabular-nums text-highlighted"><AppText :text="salary(vacancy.salary)" /></p>
    </div>

    <p v-if="vacancy.status === 'pending'" class="flex items-start gap-2 rounded-xl bg-honey-50 px-3 py-2 text-sm text-honey-800 dark:bg-honey-950 dark:text-honey-200">
      <UIcon name="i-lucide-hourglass" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ t('dashboard.vacancies.pendingNote') }}
    </p>
    <p v-else-if="vacancy.status === 'rejected'" class="flex items-start gap-2 rounded-xl bg-pomegranate-50 px-3 py-2 text-sm text-pomegranate-700 dark:bg-pomegranate-950 dark:text-pomegranate-300">
      <UIcon name="i-lucide-circle-alert" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ t('dashboard.vacancies.rejectedNote') }}
    </p>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-default pt-3.5">
      <ul class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-muted">
        <li class="inline-flex items-center gap-1.5"><UIcon name="i-lucide-inbox" class="size-4" aria-hidden="true" />{{ t('dashboard.vacancies.applicationsCount', { n: vacancy.applications_count ?? 0 }) }}</li>
        <li class="inline-flex items-center gap-1.5"><UIcon name="i-lucide-eye" class="size-4" aria-hidden="true" />{{ t('dashboard.vacancies.views', { n: vacancy.views_count }) }}</li>
        <li v-if="vacancy.expires_at && vacancy.status === 'active'" class="inline-flex items-center gap-1.5"><UIcon name="i-lucide-calendar-clock" class="size-4" aria-hidden="true" />{{ t('vacancy.detail.expiresTill', { date: date(vacancy.expires_at) }) }}</li>
      </ul>
      <div class="flex flex-wrap items-center gap-2">
        <UButton size="sm" variant="soft" icon="i-lucide-inbox" :label="t('dashboard.vacancies.open')" :to="localePath(`/kabinet/vakansiyalar/${vacancy.id}`)" />
        <DashboardVacancyActions :vacancy="vacancy" @updated="v => $emit('updated', v)" @deleted="$emit('deleted')" />
      </div>
    </div>
  </article>
</template>
