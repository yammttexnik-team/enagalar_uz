<script setup lang="ts">
/**
 * "Matnni joylash (AI)": eski Telegram e'lon matnidan forma maydonlarini oladi.
 * Hech narsa saqlanmaydi — maydonlar `parsed` hodisasi bilan formaga beriladi.
 */
const props = defineProps<{ type: 'nanny' | 'vacancy' }>()
const emit = defineEmits<{ parsed: [fields: Record<string, unknown>] }>()
const { t } = useI18n()
const api = useApi()
const toast = useToast()
const { toastError } = useApiError()

const MIN = 20
const MAX = 4000
const open = ref(false)
const text = ref('')
const busy = ref(false)
const tooShort = computed(() => text.value.trim().length < MIN)

async function submit() {
  if (tooShort.value) return
  busy.value = true
  try {
    const res = await api<{ data: { fields: Record<string, unknown> } }>('/ai/parse-listing', { method: 'POST', body: { type: props.type, text: text.value.trim() } })
    const n = Object.keys(res.data.fields).length
    if (!n) {
      toast.add({ title: t('dashboard.aiPaste.empty'), color: 'warning', icon: 'i-lucide-info' })
      return
    }
    emit('parsed', res.data.fields)
    toast.add({ title: t('dashboard.aiPaste.filled', { n }), color: 'info', icon: 'i-lucide-sparkles' })
    open.value = false
    text.value = ''
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 429) toast.add({ title: t('dashboard.aiPaste.limit'), color: 'error', icon: 'i-lucide-circle-alert' })
    else if (p.status === 503) toast.add({ title: t('dashboard.aiPaste.unavailable'), color: 'error', icon: 'i-lucide-circle-alert' })
    else toastError(e)
  }
  finally { busy.value = false }
}
</script>

<template>
  <UButton type="button" color="primary" variant="soft" icon="i-lucide-sparkles" :label="t('dashboard.aiPaste.button')" @click="open = true" />
  <UModal v-model:open="open" :title="t('dashboard.aiPaste.title')" :description="t('dashboard.aiPaste.description')" :close="{ 'aria-label': t('search.close') }">
    <template #body>
      <UFormField :label="t('dashboard.aiPaste.label')" :hint="`${text.length}/${MAX}`">
        <UTextarea v-model="text" :rows="8" :maxlength="MAX" class="w-full" autofocus :placeholder="type === 'nanny' ? t('dashboard.aiPaste.placeholderNanny') : t('dashboard.aiPaste.placeholderVacancy')" />
      </UFormField>
      <p class="mt-3 flex items-start gap-2 text-sm text-muted">
        <UIcon name="i-lucide-shield-check" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
        <span>{{ t('dashboard.aiPaste.privacy') }}</span>
      </p>
    </template>
    <template #footer>
      <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end">
        <span v-if="text && tooShort" class="text-sm text-muted sm:me-auto">{{ t('dashboard.aiPaste.tooShort', { n: MIN }) }}</span>
        <UButton color="neutral" variant="outline" class="justify-center" :label="t('dashboard.common.cancel')" :disabled="busy" @click="open = false" />
        <UButton class="justify-center" icon="i-lucide-sparkles" :loading="busy" :disabled="tooShort" :label="t('dashboard.aiPaste.submit')" @click="submit" />
      </div>
    </template>
  </UModal>
</template>
