<script setup lang="ts">
const props = defineProps<{ conversationId: number }>()
const open = defineModel<boolean>('open', { required: true })
const { t } = useI18n()
const api = useApi()
const { toastError, ok } = useApiError()
const reason = ref<typeof REPORT_REASONS[number]>('spam')
const comment = ref('')
const busy = ref(false)
const items = computed(() => REPORT_REASONS.map(r => ({ value: r, label: t(`chat.report.reasons.${r}`) })))

async function submit() {
  busy.value = true
  try {
    await api(`/conversations/${props.conversationId}/report`, { method: 'POST', body: { reason: reason.value, comment: comment.value.trim() || undefined } })
    ok(t('chat.report.done'))
    open.value = false
    comment.value = ''
  }
  catch (e) { toastError(e) }
  finally { busy.value = false }
}
</script>

<template>
  <UModal v-model:open="open" :title="t('chat.report.title')" :description="t('chat.report.text')" :close="{ 'aria-label': t('search.close') }">
    <template #body>
      <URadioGroup v-model="reason" :items="items" :legend="t('chat.report.reason')" />
      <UFormField class="mt-5" :label="t('chat.report.comment')" :hint="`${comment.length}/500`">
        <UTextarea v-model="comment" :rows="3" :maxlength="500" class="w-full" />
      </UFormField>
    </template>
    <template #footer>
      <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <UButton color="neutral" variant="outline" class="justify-center" :label="t('chat.cancel')" :disabled="busy" @click="open = false" />
        <UButton color="error" class="justify-center" icon="i-lucide-flag" :loading="busy" :label="t('chat.report.send')" @click="submit" />
      </div>
    </template>
  </UModal>
</template>
