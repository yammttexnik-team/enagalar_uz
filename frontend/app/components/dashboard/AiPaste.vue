<script setup lang="ts">
/**
 * "Matnni joylash (AI)": eski Telegram e'lon matnidan forma maydonlarini oladi.
 * Hech narsa saqlanmaydi — maydonlar `parsed` hodisasi bilan formaga beriladi.
 */
const props = defineProps<{ type: 'nanny' | 'vacancy', inline?: boolean }>()
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
  <!-- Asosiy kirish: yangi e'lon/anketada forma tepasida ochiq turadi -->
  <section v-if="inline" class="rounded-3xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-800 dark:bg-brand-950/60 sm:p-6">
    <h2 class="flex items-center gap-2 font-display text-lg font-extrabold text-highlighted">
      <UIcon name="i-lucide-sparkles" class="size-5 text-primary" aria-hidden="true" />{{ t('dashboard.aiPaste.inlineTitle') }}
    </h2>
    <UTextarea v-model="text" :rows="6" :maxlength="MAX" class="mt-4 w-full" :aria-label="t('dashboard.aiPaste.label')" :placeholder="type === 'nanny' ? t('dashboard.aiPaste.placeholderNanny') : t('dashboard.aiPaste.placeholderVacancy')" />
    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="flex items-start gap-2 text-sm text-muted">
        <UIcon name="i-lucide-shield-check" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
        <span>{{ text && tooShort ? t('dashboard.aiPaste.tooShort', { n: MIN }) : t('dashboard.aiPaste.privacy') }}</span>
      </p>
      <UButton type="button" class="shrink-0 justify-center" icon="i-lucide-sparkles" :loading="busy" :disabled="tooShort" :label="t('dashboard.aiPaste.submit')" @click="submit" />
    </div>
  </section>
  <UButton v-else type="button" color="primary" variant="soft" icon="i-lucide-sparkles" :label="t('dashboard.aiPaste.button')" @click="open = true" />
  <UModal v-if="!inline" v-model:open="open" :title="t('dashboard.aiPaste.title')" :description="t('dashboard.aiPaste.description')" :close="{ 'aria-label': t('search.close') }">
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
