<script setup lang="ts">
const props = defineProps<{ application: Application }>()
const emit = defineEmits<{ status: [s: ApplicationStatus] }>()
const { t } = useI18n()
const api = useApi()
const { relativeTime } = useFormat()
const { toastError, ok } = useApiError()
const busy = ref<string | null>(null)

const actions = [
  { value: 'viewed' as const, icon: 'i-lucide-eye', color: 'neutral' as const },
  { value: 'shortlisted' as const, icon: 'i-lucide-star', color: 'primary' as const },
  { value: 'rejected' as const, icon: 'i-lucide-x', color: 'error' as const },
]

async function setStatus(s: ApplicationStatus) {
  busy.value = s
  try {
    await api(`/applications/${props.application.id}`, { method: 'PATCH', body: { status: s } })
    emit('status', s)
    ok(t(`dashboard.applications.marked.${s}`))
  }
  catch (e) { toastError(e) }
  finally { busy.value = null }
}
</script>

<template>
  <div class="surface overflow-hidden p-0!">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-default px-5 py-3">
      <div class="flex items-center gap-2">
        <DashboardStatusBadge kind="application" :status="application.status" />
        <span class="text-sm text-muted">{{ relativeTime(application.created_at) }}</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <UButton
          v-for="a in actions"
          :key="a.value"
          size="sm"
          :color="a.color"
          :variant="application.status === a.value ? 'solid' : 'outline'"
          :icon="a.icon"
          :loading="busy === a.value"
          :disabled="!!busy || application.status === a.value"
          :label="t(`dashboard.applications.action.${a.value}`)"
          @click="setStatus(a.value)"
        />
      </div>
    </div>
    <blockquote v-if="application.message" class="mx-5 mt-4 rounded-xl border-l-4 border-brand-300 bg-brand-50 px-4 py-3 text-[15px] text-default dark:border-brand-700 dark:bg-brand-950">
      {{ application.message }}
    </blockquote>
    <div class="p-3"><NannyCard v-if="application.nanny" :nanny="application.nanny" class="border-0! shadow-none!" /></div>
  </div>
</template>
