<script setup lang="ts">
const { t } = useI18n()
const route = useRoute()
const api = useApi()
const localePath = useLocalePath()
definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.vacancyForm.editSeo'), robots: 'noindex' })

const { data: vacancy, status } = await useAsyncData(`my-vacancy-${route.params.id}`, () => fetchMyVacancy(api, route.params.id as string))
const editable = computed(() => !!vacancy.value && ['draft', 'pending', 'active', 'rejected'].includes(vacancy.value.status))
</script>

<template>
  <div>
    <DashboardPageHead :title="t('dashboard.vacancyForm.edit')" :back="{ to: localePath(`/kabinet/vakansiyalar/${route.params.id}`), label: t('dashboard.common.back') }" />
    <DashboardRoleGate role="parent">
      <DashboardVacancyForm v-if="vacancy && editable" :vacancy="vacancy" />
      <div v-else-if="status !== 'pending'" class="surface px-6 py-12 text-center">
        <p class="text-muted">{{ vacancy ? t('dashboard.vacancyForm.notEditable') : t('vacancy.detail.notFound') }}</p>
        <UButton class="mt-5" :label="t('dashboard.vacancies.title')" :to="localePath('/kabinet/vakansiyalar')" />
      </div>
    </DashboardRoleGate>
  </div>
</template>
