<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.vacancyForm.newSeo'), robots: 'noindex' })
</script>

<template>
  <div>
    <DashboardPageHead :title="t('dashboard.vacancies.new')" :subtitle="t('dashboard.vacancyForm.newSubtitle')" :back="{ to: localePath('/kabinet/vakansiyalar'), label: t('dashboard.vacancies.title') }" />
    <DashboardRoleGate role="parent">
      <DashboardVacancyForm />
    </DashboardRoleGate>
  </div>
</template>
