<script setup lang="ts">
const props = defineProps<{ badge: VerificationState['available_badges'][number], docs: VerificationState['documents'] }>()
const emit = defineEmits<{ changed: [] }>()
const { t, locale } = useI18n()
const api = useApi()
const { date } = useFormat()
const { toastError, ok } = useApiError()
const localizedName = useLocalizedName()

const health = computed(() => ['medical-certificate', 'dispensary-clear'].includes(props.badge.slug))
const pending = computed(() => props.docs.find(d => d.status === 'pending'))
const lastRejected = computed(() => (!pending.value && !props.badge.held ? props.docs.find(d => d.status === 'rejected') : undefined))
const description = computed(() => (locale.value === 'ru' ? props.badge.description.ru : props.badge.description.uz))
const open = ref(false)

const file = ref<File | null>(null)
const issued = ref('')
const consent = ref(false)
const errors = ref<Record<string, string>>({})
const sending = ref(false)
const delOpen = ref(false)
const deleting = ref(false)

const today = new Date()
const ymd = (d: Date) => d.toISOString().slice(0, 10)
const maxIssued = ymd(today)
const minIssued = computed(() => (props.badge.validity_days ? ymd(new Date(today.getTime() - props.badge.validity_days * 86400000)) : undefined))
const id = computed(() => `doc-${props.badge.slug}`)

function validate() {
  const e: Record<string, string> = {}
  if (!file.value) e.file = t('dashboard.verification.err.file')
  if (!issued.value) e.issued_at = t('dashboard.validation.required')
  else if (issued.value > maxIssued) e.issued_at = t('dashboard.verification.err.issuedFuture')
  else if (minIssued.value && issued.value < minIssued.value) e.issued_at = t('dashboard.verification.err.issuedOld', { n: props.badge.validity_days })
  if (!consent.value) e.consent = t('dashboard.verification.err.consent')
  errors.value = e
  return !Object.keys(e).length
}

watch(file, () => { delete errors.value.file })
watch(issued, () => { delete errors.value.issued_at })
watch(consent, () => { delete errors.value.consent })

async function submit() {
  if (!validate()) return
  sending.value = true
  const body = new FormData()
  body.append('badge', props.badge.slug)
  body.append('file', file.value!)
  body.append('issued_at', issued.value)
  body.append('consent', '1')
  try {
    await api('/my/verification/documents', { method: 'POST', body })
    ok(t('dashboard.verification.docSent'))
    open.value = false
    file.value = null; issued.value = ''; consent.value = false
    emit('changed')
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 422) errors.value = p.fields
    else { toastError(e); if (p.status === 409) emit('changed') }
  }
  finally { sending.value = false }
}

async function remove() {
  if (!pending.value) return
  deleting.value = true
  try {
    await api(`/my/verification/documents/${pending.value.id}`, { method: 'DELETE' })
    ok(t('dashboard.verification.docDeleted'))
    delOpen.value = false
    emit('changed')
  }
  catch (e) { toastError(e); emit('changed') }
  finally { deleting.value = false }
}
const err = (k: string) => errors.value[k]
</script>

<template>
  <article class="surface p-5 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="font-display text-lg font-extrabold text-highlighted">{{ localizedName(badge.name) }}</h3>
        <p v-if="description" class="mt-1 text-sm text-muted">{{ description }}</p>
        <p v-if="badge.validity_days" class="mt-1 text-xs text-dimmed">{{ t('dashboard.verification.validity', { n: badge.validity_days }) }}</p>
      </div>
      <UBadge v-if="badge.held" color="success" variant="subtle" icon="i-lucide-circle-check" :label="t('dashboard.verification.held')" />
      <UBadge v-else-if="pending" color="warning" variant="subtle" icon="i-lucide-hourglass" :label="t('dashboard.status.verification.pending')" />
    </div>

    <p v-if="badge.held && badge.expires_at" class="mt-3 text-sm text-leaf-700 dark:text-leaf-400">{{ t('dashboard.verification.expires', { date: date(badge.expires_at) }) }}</p>
    <p v-if="lastRejected" class="mt-3 rounded-xl bg-pomegranate-50 px-3 py-2 text-sm text-pomegranate-700 dark:bg-pomegranate-950 dark:text-pomegranate-300">
      {{ t('dashboard.verification.docRejected') }}<template v-if="lastRejected.rejection_reason">: {{ lastRejected.rejection_reason }}</template>
    </p>

    <div v-if="pending" class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-honey-50 px-4 py-3 text-sm text-honey-800 dark:bg-honey-950 dark:text-honey-200">
      <span>{{ t('dashboard.verification.docPending') }}</span>
      <UButton size="sm" color="error" variant="soft" icon="i-lucide-trash-2" :label="t('dashboard.verification.docDelete')" @click="delOpen = true" />
    </div>

    <template v-else>
      <UButton
        v-if="!open"
        class="mt-4"
        :color="badge.held ? 'neutral' : 'primary'"
        :variant="badge.held ? 'outline' : 'solid'"
        icon="i-lucide-upload"
        :label="badge.held ? t('dashboard.verification.renew') : t('dashboard.verification.upload')"
        @click="open = true"
      />
      <form v-else class="mt-5 space-y-4 border-t border-default pt-5" novalidate @submit.prevent="submit">
        <p v-if="health" class="flex items-start gap-2 rounded-xl bg-azure-50 px-3 py-2.5 text-sm text-azure-800 dark:bg-azure-950 dark:text-azure-200">
          <UIcon name="i-lucide-heart-pulse" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ t('dashboard.verification.healthNote') }}
        </p>
        <UFormField :label="t('dashboard.verification.docFile')" :error="err('file')" required>
          <DashboardFilePicker v-model="file" :input-id="`${id}-file`" :accept="['image/jpeg', 'image/png', 'image/webp', 'application/pdf']" :max-mb="10" :label="t('dashboard.verification.pickFile')" :hint="t('dashboard.verification.fileHint')" />
        </UFormField>
        <UFormField :label="t('dashboard.verification.issuedAt')" :error="err('issued_at')" required class="max-w-xs">
          <UInput v-model="issued" type="date" :min="minIssued" :max="maxIssued" class="w-full" />
        </UFormField>
        <UFormField :error="err('consent')">
          <UCheckbox
            v-model="consent"
            :label="health ? t('dashboard.verification.healthConsentLabel') : t('dashboard.verification.consentLabel')"
            :description="health ? t('dashboard.verification.healthConsentText') : t('dashboard.verification.docConsentText')"
            :ui="{ description: 'text-[13px] leading-relaxed' }"
          />
        </UFormField>
        <div class="flex flex-wrap gap-2">
          <UButton type="submit" icon="i-lucide-send" :loading="sending" :label="t('dashboard.verification.docSubmit')" />
          <UButton type="button" color="neutral" variant="ghost" :label="t('dashboard.common.cancel')" @click="open = false" />
        </div>
      </form>
    </template>

    <DashboardConfirm v-model:open="delOpen" color="error" :title="t('dashboard.verification.docDeleteTitle')" :text="t('dashboard.verification.docDeleteText')" :confirm="t('dashboard.common.delete')" :loading="deleting" @confirm="remove" />
  </article>
</template>
