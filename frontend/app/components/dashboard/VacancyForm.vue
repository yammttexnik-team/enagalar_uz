<script setup lang="ts">
const props = defineProps<{ vacancy?: OwnVacancy }>()
const { t } = useI18n()
const api = useApi()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const { enumLabel, childAge } = useFormat()
const { toastError, ok } = useApiError()
const { data: regions } = await useRegions()

const v = props.vacancy
const NONE = 'none'
const digits = (n: number | null | undefined) => (n ? String(n) : '')

const form = reactive({
  title: v?.title ?? '',
  description: v?.description ?? '',
  region_id: (v?.region_id ?? undefined) as number | undefined,
  district_id: (v?.district_id ?? undefined) as number | undefined,
  work_format: v?.work_format ?? '',
  schedule: v?.schedule ?? '',
  salary_min: digits(v?.salary.min),
  salary_max: digits(v?.salary.max),
  salary_unit: v?.salary.unit ?? 'month',
  children_count: v?.children_count ?? 1,
  ages: Array.from({ length: v?.children_count ?? 1 }, (_, i) => (v?.children_ages[i] !== undefined ? String(v.children_ages[i]) : NONE)),
  languages: [...(v?.languages ?? [])],
  live_in: v?.live_in ?? false,
  is_urgent: v?.is_urgent ?? false,
  starts_at: v?.starts_at ?? '',
})

watch(() => form.children_count, (n) => {
  const count = Math.min(10, Math.max(1, n || 1))
  while (form.ages.length < count) form.ages.push(NONE)
  form.ages.length = count
})
watch(() => form.region_id, (_, old) => { if (old !== undefined) form.district_id = undefined })

const regionItems = computed(() => regions.value.map(r => ({ label: localizedName(r.name), value: r.id })))
const districtItems = computed(() => (regions.value.find(r => r.id === form.region_id)?.districts ?? []).map(d => ({ label: localizedName(d.name), value: d.id })))
const formatItems = computed(() => WORK_FORMATS.map(f => ({ label: enumLabel('workFormat', f), value: f })))
const langItems = computed(() => LANGUAGES.map(l => ({ label: enumLabel('language', l), value: l })))
const unitItems = computed(() => PRICE_UNITS.map(u => ({ label: enumLabel('priceUnit', u), value: u })))
const ageItems = computed(() => [{ label: t('dashboard.vacancyForm.ageUnknown'), value: NONE }, ...Array.from({ length: 18 }, (_, i) => ({ label: childAge(i), value: String(i) }))])

const errors = ref<Record<string, string>>({})
const topError = ref('')
const saving = ref(false)
const today = new Date().toISOString().slice(0, 10)

function onlyDigits(key: 'salary_min' | 'salary_max', e: Event) {
  const val = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 10)
  form[key] = val
}
const spaced = (s: string) => s.replace(/\B(?=(\d{3})+(?!\d))/g, ' ')

function validate() {
  const e: Record<string, string> = {}
  if (!form.title.trim()) e.title = t('dashboard.validation.required')
  else if (form.title.length > 120) e.title = t('dashboard.validation.max', { n: 120 })
  if (form.description.trim().length < 20) e.description = t('dashboard.validation.min', { n: 20 })
  else if (form.description.length > 3000) e.description = t('dashboard.validation.max', { n: 3000 })
  if (!form.region_id) e.region_id = t('dashboard.validation.required')
  if (!form.work_format) e.work_format = t('dashboard.validation.required')
  if (form.schedule.length > 255) e.schedule = t('dashboard.validation.max', { n: 255 })
  if (form.salary_min && form.salary_max && Number(form.salary_max) < Number(form.salary_min)) e.salary_max = t('dashboard.vacancyForm.salaryOrder')
  if (!(form.children_count >= 1 && form.children_count <= 10)) e.children_count = t('dashboard.vacancyForm.childrenRange')
  errors.value = e
  return Object.keys(e).length === 0
}

function scrollToError() {
  nextTick(() => document.querySelector('[data-invalid="true"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }))
}

async function submit() {
  topError.value = ''
  if (!validate()) return scrollToError()
  saving.value = true
  const body = {
    region_id: form.region_id,
    district_id: form.district_id ?? null,
    title: form.title.trim(),
    description: form.description.trim(),
    work_format: form.work_format,
    schedule: form.schedule.trim() || null,
    salary_min: form.salary_min ? Number(form.salary_min) : null,
    salary_max: form.salary_max ? Number(form.salary_max) : null,
    salary_unit: form.salary_unit,
    children_count: form.children_count,
    children_ages: form.ages.filter(a => a !== NONE).map(Number),
    languages: form.languages,
    live_in: form.live_in,
    is_urgent: form.is_urgent,
    starts_at: form.starts_at || null,
  }
  try {
    const res = props.vacancy
      ? await api<{ data: OwnVacancy }>(`/my/vacancies/${props.vacancy.id}`, { method: 'PUT', body })
      : await api<{ data: OwnVacancy }>('/my/vacancies', { method: 'POST', body })
    ok(props.vacancy ? t('dashboard.vacancyForm.updated') : t('dashboard.vacancyForm.created'))
    await navigateTo(localePath(`/kabinet/vakansiyalar/${res.data.id}`))
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 422) {
      const mapped: Record<string, string> = {}
      for (const [k, msg] of Object.entries(p.fields)) mapped[k.startsWith('children_ages') ? 'children_ages' : k] = msg
      errors.value = mapped
      if (mapped.vacancy) topError.value = mapped.vacancy
      scrollToError()
    }
    else toastError(e)
  }
  finally {
    saving.value = false
  }
}

// Maydon oʻzgarsa, uning xatosi yoʻqoladi.
for (const key of Object.keys(form) as (keyof typeof form)[]) {
  watch(() => form[key], () => {
    const k = key === 'ages' ? 'children_ages' : key
    if (errors.value[k]) { const { [k]: _, ...rest } = errors.value; errors.value = rest }
  }, { deep: true })
}

const err = (k: string) => errors.value[k]
</script>

<template>
  <form class="space-y-6" novalidate @submit.prevent="submit">
    <p class="flex items-start gap-3 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800 dark:border-brand-800 dark:bg-brand-950 dark:text-brand-100">
      <UIcon name="i-lucide-shield-check" class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
      <span>{{ vacancy && vacancy.status === 'active' ? t('dashboard.vacancyForm.noteEdit') : t('dashboard.vacancyForm.note') }}</span>
    </p>
    <p v-if="topError" class="rounded-xl bg-pomegranate-50 px-4 py-3 text-sm text-pomegranate-700 dark:bg-pomegranate-950 dark:text-pomegranate-300" role="alert">{{ topError }}</p>

    <section class="surface space-y-5 p-5 sm:p-7">
      <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('dashboard.vacancyForm.about') }}</h2>
      <UFormField :label="t('dashboard.vacancyForm.title')" :error="err('title')" required :hint="`${form.title.length}/120`" :data-invalid="!!err('title')">
        <UInput v-model="form.title" class="w-full" maxlength="120" size="lg" :placeholder="t('dashboard.vacancyForm.titlePlaceholder')" />
      </UFormField>
      <UFormField :label="t('dashboard.vacancyForm.description')" :error="err('description')" required :data-invalid="!!err('description')">
        <UTextarea v-model="form.description" class="w-full" :rows="5" maxlength="3000" :placeholder="t('dashboard.vacancyForm.descriptionPlaceholder')" />
        <template #hint>
          <span :class="form.description.trim().length < 20 ? 'text-muted' : 'text-leaf-700 dark:text-leaf-400'">{{ form.description.trim().length }}/3000 · {{ t('dashboard.validation.min', { n: 20 }) }}</span>
        </template>
      </UFormField>
    </section>

    <section class="surface space-y-5 p-5 sm:p-7">
      <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('dashboard.vacancyForm.where') }}</h2>
      <div class="grid gap-5 sm:grid-cols-2">
        <UFormField :label="t('search.region')" :error="err('region_id')" required :data-invalid="!!err('region_id')">
          <USelect v-model="form.region_id" :items="regionItems" icon="i-lucide-map-pin" class="w-full" :placeholder="t('dashboard.common.choose')" />
        </UFormField>
        <UFormField :label="t('search.district')" :error="err('district_id')" :data-invalid="!!err('district_id')">
          <USelect v-model="form.district_id" :items="districtItems" :disabled="!form.region_id" class="w-full" :placeholder="form.region_id ? t('dashboard.common.choose') : t('search.pickRegionFirst')" />
        </UFormField>
      </div>
      <UFormField :label="t('search.workFormat')" :error="err('work_format')" required :data-invalid="!!err('work_format')">
        <SearchChips :label="t('search.workFormat')" :items="formatItems" :model-value="form.work_format" @update:model-value="x => (form.work_format = x ?? '')" />
      </UFormField>
      <div class="grid gap-5 sm:grid-cols-2">
        <UFormField :label="t('vacancy.detail.schedule')" :error="err('schedule')" :data-invalid="!!err('schedule')">
          <UInput v-model="form.schedule" class="w-full" maxlength="255" icon="i-lucide-clock" :placeholder="t('dashboard.vacancyForm.schedulePlaceholder')" />
        </UFormField>
        <UFormField :label="t('vacancy.detail.startsAt')" :error="err('starts_at')" :data-invalid="!!err('starts_at')">
          <UInput v-model="form.starts_at" type="date" :min="today" class="w-full" icon="i-lucide-calendar-check" />
        </UFormField>
      </div>
      <div class="flex flex-col gap-3 sm:flex-row sm:gap-8">
        <USwitch v-model="form.live_in" :label="t('vacancy.detail.liveIn')" />
        <USwitch v-model="form.is_urgent" :label="t('vacancy.urgent')" />
      </div>
    </section>

    <section class="surface space-y-5 p-5 sm:p-7">
      <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('vacancy.detail.salary') }}</h2>
      <div class="grid gap-5 sm:grid-cols-3">
        <UFormField :label="t('dashboard.vacancyForm.salaryMin')" :error="err('salary_min')" :data-invalid="!!err('salary_min')">
          <UInput :model-value="spaced(form.salary_min)" inputmode="numeric" class="w-full" :placeholder="t('search.pricePlaceholder')" @input="onlyDigits('salary_min', $event)" />
        </UFormField>
        <UFormField :label="t('dashboard.vacancyForm.salaryMax')" :error="err('salary_max')" :data-invalid="!!err('salary_max')">
          <UInput :model-value="spaced(form.salary_max)" inputmode="numeric" class="w-full" :placeholder="t('search.pricePlaceholder')" @input="onlyDigits('salary_max', $event)" />
        </UFormField>
        <UFormField :label="t('search.unit')" :error="err('salary_unit')">
          <USelect v-model="form.salary_unit" :items="unitItems" class="w-full" />
        </UFormField>
      </div>
      <p class="text-sm text-muted">{{ t('dashboard.vacancyForm.salaryHint') }}</p>
    </section>

    <section class="surface space-y-5 p-5 sm:p-7">
      <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('vacancy.detail.children') }}</h2>
      <UFormField :label="t('dashboard.vacancyForm.childrenCount')" :error="err('children_count')" :data-invalid="!!err('children_count')">
        <UInputNumber v-model="form.children_count" :min="1" :max="10" class="w-40" :aria-label="t('dashboard.vacancyForm.childrenCount')" />
      </UFormField>
      <UFormField :label="t('dashboard.vacancyForm.childrenAges')" :error="err('children_ages')" :data-invalid="!!err('children_ages')">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <USelect v-for="(_, i) in form.ages" :key="i" v-model="form.ages[i]" :items="ageItems" icon="i-lucide-baby" class="w-full" :aria-label="t('dashboard.vacancyForm.childN', { n: i + 1 })" />
        </div>
      </UFormField>
      <UFormField :label="t('search.language')" :error="err('languages')" :description="t('dashboard.vacancyForm.languagesHint')">
        <DashboardChipsMulti v-model="form.languages" :items="langItems" :label="t('search.language')" />
      </UFormField>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
      <UButton size="lg" color="neutral" variant="outline" class="justify-center" :label="t('dashboard.common.cancel')" :to="localePath(vacancy ? `/kabinet/vakansiyalar/${vacancy.id}` : '/kabinet/vakansiyalar')" />
      <UButton type="submit" size="lg" class="justify-center" :loading="saving" icon="i-lucide-send" :label="vacancy ? t('dashboard.common.save') : t('dashboard.vacancyForm.submit')" />
    </div>
  </form>
</template>
