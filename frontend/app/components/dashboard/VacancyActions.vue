<script setup lang="ts">
const props = defineProps<{ vacancy: OwnVacancy, size?: 'sm' | 'md', detail?: boolean }>()
const emit = defineEmits<{ updated: [v: OwnVacancy], deleted: [] }>()
const { t } = useI18n()
const api = useApi()
const localePath = useLocalePath()
const { toastError, ok } = useApiError()

const canEdit = computed(() => ['draft', 'pending', 'active', 'rejected'].includes(props.vacancy.status))
const canClose = computed(() => ['pending', 'active'].includes(props.vacancy.status))

const closeOpen = ref(false)
const delOpen = ref(false)
const found = ref<'found' | 'closed'>('found')
const busy = ref(false)

async function doClose() {
  busy.value = true
  try {
    const res = await api<{ data: OwnVacancy }>(`/my/vacancies/${props.vacancy.id}/close`, { method: 'POST', body: { found: found.value === 'found' } })
    ok(t('dashboard.vacancies.closedToast'))
    closeOpen.value = false
    emit('updated', { ...props.vacancy, ...res.data })
  }
  catch (e) { toastError(e) }
  finally { busy.value = false }
}

async function doDelete() {
  busy.value = true
  try {
    await api(`/my/vacancies/${props.vacancy.id}`, { method: 'DELETE' })
    ok(t('dashboard.vacancies.deletedToast'))
    delOpen.value = false
    emit('deleted')
  }
  catch (e) { toastError(e) }
  finally { busy.value = false }
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <UButton v-if="canEdit" :size="size || 'sm'" color="neutral" variant="outline" icon="i-lucide-pencil" :label="t('dashboard.common.edit')" :to="localePath(`/kabinet/vakansiyalar/${vacancy.id}/tahrirlash`)" />
    <UButton v-if="canClose" :size="size || 'sm'" color="neutral" variant="outline" icon="i-lucide-circle-check" :label="t('dashboard.vacancies.close')" @click="closeOpen = true" />
    <UButton :size="size || 'sm'" color="error" variant="ghost" icon="i-lucide-trash-2" :label="t('dashboard.common.delete')" @click="delOpen = true" />

    <DashboardConfirm v-model:open="closeOpen" :title="t('dashboard.vacancies.closeTitle')" :text="t('dashboard.vacancies.closeText')" :confirm="t('dashboard.vacancies.close')" :loading="busy" @confirm="doClose">
      <URadioGroup v-model="found" :items="[{ label: t('dashboard.vacancies.closeFound'), value: 'found' }, { label: t('dashboard.vacancies.closeOther'), value: 'closed' }]" />
    </DashboardConfirm>
    <DashboardConfirm v-model:open="delOpen" color="error" :title="t('dashboard.vacancies.deleteTitle')" :text="t('dashboard.vacancies.deleteText', { title: vacancy.title })" :confirm="t('dashboard.common.delete')" :loading="busy" @confirm="doDelete" />
  </div>
</template>
