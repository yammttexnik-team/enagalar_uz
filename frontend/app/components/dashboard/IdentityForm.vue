<script setup lang="ts">
const emit = defineEmits<{ submitted: [] }>()
const { t } = useI18n()
const api = useApi()
const { toastError, ok } = useApiError()

const form = reactive({ full_name: '', series: '', number: '', pinfl: '', birth_date: '', consent: false })
const photo = ref<File | null>(null)
const errors = ref<Record<string, string>>({})
const sending = ref(false)

const ymd = (d: Date) => d.toISOString().slice(0, 10)
const now = new Date()
const maxBirth = ymd(new Date(now.getFullYear() - 18, now.getMonth(), now.getDate()))
const minBirth = ymd(new Date(now.getFullYear() - 80, now.getMonth(), now.getDate() + 1))

// Niqoblar: seriya — 2 ta lotin harfi (katta), raqam — 7 ta, JShShIR — 14 ta raqam.
function mask(key: 'series' | 'number' | 'pinfl', e: Event, fn: (v: string) => string) {
  const el = e.target as HTMLInputElement
  el.value = fn(el.value)
  form[key] = el.value
}
const onSeries = (e: Event) => mask('series', e, v => v.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 2))
const onNumber = (e: Event) => mask('number', e, v => v.replace(/\D/g, '').slice(0, 7))
const onPinfl = (e: Event) => mask('pinfl', e, v => v.replace(/\D/g, '').slice(0, 14))

function validate() {
  const e: Record<string, string> = {}
  if (form.full_name.trim().length < 5) e.full_name = t('dashboard.verification.err.fullName')
  if (!/^[A-Z]{2}$/.test(form.series)) e.passport_series = t('dashboard.verification.err.series')
  if (!/^\d{7}$/.test(form.number)) e.passport_number = t('dashboard.verification.err.number')
  if (!/^\d{14}$/.test(form.pinfl)) e.pinfl = t('dashboard.verification.err.pinfl')
  if (!form.birth_date) e.birth_date = t('dashboard.validation.required')
  else if (form.birth_date > maxBirth) e.birth_date = t('dashboard.verification.err.age18')
  else if (form.birth_date < minBirth) e.birth_date = t('dashboard.verification.err.age80')
  if (!photo.value) e.photo = t('dashboard.verification.err.photo')
  if (!form.consent) e.consent = t('dashboard.verification.err.consent')
  errors.value = e
  return !Object.keys(e).length
}

// Maydon oʻzgarsa, uning xatosi yoʻqoladi.
const fieldMap = { full_name: 'full_name', series: 'passport_series', number: 'passport_number', pinfl: 'pinfl', birth_date: 'birth_date', consent: 'consent' } as const
for (const [k, errKey] of Object.entries(fieldMap)) {
  watch(() => form[k as keyof typeof form], () => { if (errors.value[errKey]) { const { [errKey]: _, ...rest } = errors.value; errors.value = rest } })
}
watch(photo, () => { if (errors.value.photo) { const { photo: _, ...rest } = errors.value; errors.value = rest } })

async function submit() {
  if (!validate()) {
    nextTick(() => document.querySelector('[data-invalid="true"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }))
    return
  }
  sending.value = true
  const body = new FormData()
  body.append('full_name', form.full_name.trim())
  body.append('passport_series', form.series)
  body.append('passport_number', form.number)
  body.append('pinfl', form.pinfl)
  body.append('birth_date', form.birth_date)
  body.append('photo', photo.value!)
  body.append('consent', '1')
  try {
    await api('/my/verification/identity', { method: 'POST', body })
    ok(t('dashboard.verification.sent'))
    emit('submitted')
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 422) errors.value = p.fields
    else toastError(e)
    if (p.status === 409) emit('submitted')
  }
  finally { sending.value = false }
}
const err = (k: string) => errors.value[k]
</script>

<template>
  <form class="space-y-5" novalidate @submit.prevent="submit">
    <p class="flex items-start gap-3 rounded-2xl border border-leaf-200 bg-leaf-50 px-4 py-3 text-[15px] text-leaf-900 dark:border-leaf-800 dark:bg-leaf-950 dark:text-leaf-100">
      <UIcon name="i-lucide-lock-keyhole" class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
      <span><strong>{{ t('dashboard.verification.privacyTitle') }}</strong> {{ t('dashboard.verification.privacy') }}</span>
    </p>

    <UFormField :label="t('dashboard.verification.fullName')" :error="err('full_name')" :description="t('dashboard.verification.fullNameHint')" required :data-invalid="!!err('full_name')">
      <UInput v-model="form.full_name" class="w-full" autocomplete="name" maxlength="150" placeholder="Karimova Dilnoza Akbarovna" />
    </UFormField>

    <div class="grid gap-5 sm:grid-cols-[110px_1fr_1.4fr]">
      <UFormField :label="t('dashboard.verification.series')" :error="err('passport_series')" required :data-invalid="!!err('passport_series')">
        <UInput :model-value="form.series" class="w-full uppercase" maxlength="2" autocomplete="off" placeholder="AD" :ui="{ base: 'uppercase tracking-widest' }" @input="onSeries" />
      </UFormField>
      <UFormField :label="t('dashboard.verification.number')" :error="err('passport_number')" required :data-invalid="!!err('passport_number')">
        <UInput :model-value="form.number" class="w-full" inputmode="numeric" maxlength="7" autocomplete="off" placeholder="1234567" @input="onNumber" />
      </UFormField>
      <UFormField :label="t('dashboard.verification.pinfl')" :error="err('pinfl')" :description="t('dashboard.verification.pinflHint')" required :data-invalid="!!err('pinfl')">
        <UInput :model-value="form.pinfl" class="w-full" inputmode="numeric" maxlength="14" autocomplete="off" placeholder="12345678901234" @input="onPinfl" />
      </UFormField>
    </div>

    <UFormField :label="t('dashboard.verification.birthDate')" :error="err('birth_date')" required :data-invalid="!!err('birth_date')" class="max-w-xs">
      <UInput v-model="form.birth_date" type="date" :min="minBirth" :max="maxBirth" class="w-full" />
    </UFormField>

    <UFormField :label="t('dashboard.verification.photo')" :error="err('photo')" required :data-invalid="!!err('photo')">
      <DashboardFilePicker v-model="photo" input-id="identity-photo" :accept="['image/jpeg', 'image/png', 'image/webp']" :max-mb="8" :label="t('dashboard.verification.pickPhoto')" :hint="t('dashboard.verification.photoHint')" />
    </UFormField>

    <UFormField :error="err('consent')" :data-invalid="!!err('consent')">
      <UCheckbox v-model="form.consent" :label="t('dashboard.verification.consentLabel')" :description="t('dashboard.verification.consentText')" :ui="{ description: 'text-[13px] leading-relaxed' }" />
    </UFormField>

    <UButton type="submit" size="lg" icon="i-lucide-shield-check" class="justify-center" :loading="sending" :label="t('dashboard.verification.submit')" />
  </form>
</template>
