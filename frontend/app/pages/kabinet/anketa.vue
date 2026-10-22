<script setup lang="ts">
const { t } = useI18n()
const api = useApi()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const auth = useAuthStore()
const { enumLabel, price, plural, relativeTime } = useFormat()
const { toastError, ok } = useApiError()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.profile.seo'), robots: 'noindex' })

const { data: regions } = await useRegions()
const { data: skills } = await useSkills()
const isNanny = computed(() => !!auth.user?.roles.includes('nanny'))

const { data: profile, refresh } = await useAsyncData('my-nanny-profile', () =>
  (isNanny.value ? api<{ data: OwnNanny }>('/my/nanny-profile').then(r => r.data) : Promise.resolve(null)), { watch: [isNanny] })

const maxBirthYear = new Date().getFullYear() - 16
const digits = (n: number | null | undefined) => (n ? String(n) : '')
const form = reactive({
  region_id: undefined as number | undefined,
  district_id: undefined as number | undefined,
  birth_year: '',
  experience_years: 0,
  work_formats: [] as string[],
  age_groups: [] as string[],
  languages: [] as string[],
  skills: [] as string[],
  live_in: false,
  free_trial_day: false,
  price_amount: '',
  price_unit: 'month',
  bio: '',
})
function fill(p: OwnNanny) {
  form.region_id = p.region_id ?? undefined
  form.district_id = p.district_id ?? undefined
  form.birth_year = digits(p.birth_year)
  form.experience_years = p.experience_years ?? 0
  form.work_formats = [...p.work_formats]
  form.age_groups = [...p.age_groups]
  form.languages = [...p.languages]
  form.skills = p.skills.map(s => s.slug)
  form.live_in = p.live_in
  form.free_trial_day = p.free_trial_day
  form.price_amount = digits(p.price.amount)
  form.price_unit = p.price.unit ?? 'month'
  form.bio = p.bio ?? ''
}
if (profile.value) fill(profile.value)
watch(profile, (p) => { if (p && !dirtyLoaded) { fill(p); dirtyLoaded = true } })
let dirtyLoaded = !!profile.value
watch(() => form.region_id, (_, old) => { if (old !== undefined) form.district_id = undefined })

const step = ref(1)
const STEPS = ['basic', 'work', 'about', 'review'] as const
const errors = ref<Record<string, string>>({})
const saving = ref(false)
const submitting = ref(false)

const regionItems = computed(() => regions.value.map(r => ({ label: localizedName(r.name), value: r.id })))
const districtItems = computed(() => (regions.value.find(r => r.id === form.region_id)?.districts ?? []).map(d => ({ label: localizedName(d.name), value: d.id })))
const formatItems = computed(() => WORK_FORMATS.map(f => ({ label: enumLabel('workFormat', f), value: f })))
const ageItems = computed(() => AGE_GROUPS.map(f => ({ label: enumLabel('ageGroup', f), value: f })))
const langItems = computed(() => LANGUAGES.map(f => ({ label: enumLabel('language', f), value: f })))
const skillItems = computed(() => skills.value.map(s => ({ label: localizedName(s.name), value: s.slug })))
const unitItems = computed(() => PRICE_UNITS.map(u => ({ label: enumLabel('priceUnit', u), value: u })))
const spaced = (s: string) => s.replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
const onPrice = (e: Event) => { form.price_amount = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 9) }
const bioLen = computed(() => form.bio.trim().length)

function validateStep(n: number) {
  const e: Record<string, string> = {}
  if (n === 1) {
    if (!form.region_id) e.region_id = t('dashboard.validation.required')
    if (form.birth_year) {
      const y = Number(form.birth_year)
      if (!(y >= 1950 && y <= maxBirthYear)) e.birth_year = t('dashboard.profile.birthYearRange', { from: 1950, to: maxBirthYear })
    }
    if (!(form.experience_years >= 0 && form.experience_years <= 60)) e.experience_years = t('dashboard.profile.experienceRange')
  }
  if (n === 2) {
    if (!form.work_formats.length) e.work_formats = t('dashboard.validation.pickOne')
    if (!form.languages.length) e.languages = t('dashboard.validation.pickOne')
    if (!Number(form.price_amount)) e.price_amount = t('dashboard.validation.required')
  }
  if (n === 3) {
    if (bioLen.value < 50) e.bio = t('dashboard.validation.min', { n: 50 })
    else if (form.bio.length > 3000) e.bio = t('dashboard.validation.max', { n: 3000 })
  }
  errors.value = e
  return !Object.keys(e).length
}

function payloadFor(n: number) {
  if (n === 1) return { region_id: form.region_id, district_id: form.district_id ?? null, birth_year: form.birth_year ? Number(form.birth_year) : null, experience_years: form.experience_years }
  if (n === 2) return { work_formats: form.work_formats, age_groups: form.age_groups, languages: form.languages, skills: form.skills, live_in: form.live_in, free_trial_day: form.free_trial_day, price_amount: Number(form.price_amount) || null, price_unit: form.price_unit }
  return { bio: form.bio.trim() || null }
}

async function save(n: number): Promise<boolean> {
  saving.value = true
  try {
    profile.value = (await api<{ data: OwnNanny }>('/my/nanny-profile', { method: 'PUT', body: payloadFor(n) })).data
    return true
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 422) errors.value = p.fields
    else toastError(e)
    return false
  }
  finally { saving.value = false }
}

function top() {
  if (import.meta.client) document.getElementById('anketa-top')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function next() {
  if (step.value < 4) {
    if (!validateStep(step.value)) return
    if (!(await save(step.value))) return
    ok(t('dashboard.profile.saved'))
  }
  step.value = Math.min(4, step.value + 1)
  errors.value = {}
  top()
}
function prev() { step.value = Math.max(1, step.value - 1); errors.value = {}; top() }
async function goto(n: number) {
  if (n <= step.value) { step.value = n; errors.value = {}; return top() }
  // oldinga sakrash: joriy qadamni tekshirib, saqlaymiz
  if (n === step.value + 1) return next()
}

// "Matnni joylash (AI)": kelgan maydonlar formaga qo'shiladi, saqlash — foydalanuvchida (har qadamda).
async function applyAi(f: Record<string, any>) {
  if (typeof f.region_id === 'number') form.region_id = f.region_id
  // Viloyat o'zgarganda tuman tozalanadi (watch) — tumanni undan keyin qo'yamiz.
  await nextTick()
  if (typeof f.district_id === 'number') form.district_id = f.district_id
  if (typeof f.birth_year === 'number') form.birth_year = String(f.birth_year)
  if (typeof f.experience_years === 'number') form.experience_years = f.experience_years
  for (const k of ['work_formats', 'age_groups', 'languages', 'skills'] as const) {
    if (Array.isArray(f[k]) && f[k].length) form[k] = [...f[k]]
  }
  if (typeof f.live_in === 'boolean') form.live_in = f.live_in
  if (typeof f.free_trial_day === 'boolean') form.free_trial_day = f.free_trial_day
  if (typeof f.price_amount === 'number') form.price_amount = String(f.price_amount)
  if (typeof f.price_unit === 'string') form.price_unit = f.price_unit
  if (typeof f.bio === 'string') form.bio = f.bio
  step.value = 1
  errors.value = {}
  top()
}

// --- Ko'rib chiqish ---
const REQUIRED = [
  { key: 'region_id', step: 1, ok: () => !!form.region_id },
  { key: 'experience_years', step: 1, ok: () => form.experience_years >= 0 },
  { key: 'work_formats', step: 2, ok: () => form.work_formats.length > 0 },
  { key: 'languages', step: 2, ok: () => form.languages.length > 0 },
  { key: 'price_amount', step: 2, ok: () => Number(form.price_amount) > 0 },
  { key: 'bio', step: 3, ok: () => bioLen.value >= 50 },
]
const checklist = computed(() => REQUIRED.map(r => ({ ...r, done: r.ok() })))
const complete = computed(() => checklist.value.every(c => c.done))
const canSubmit = computed(() => !!profile.value && ['draft', 'rejected', 'hidden'].includes(profile.value.status))

async function submit() {
  submitting.value = true
  errors.value = {}
  try {
    profile.value = (await api<{ data: OwnNanny }>('/my/nanny-profile/submit', { method: 'POST' })).data
    ok(t('dashboard.profile.submitted'))
    top()
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 422) {
      errors.value = p.fields
      toastError(e, t('dashboard.profile.incomplete'))
    }
    else toastError(e)
  }
  finally { submitting.value = false }
}

const available = computed({
  get: () => profile.value?.is_available ?? false,
  async set(v) {
    if (!profile.value) return
    const old = profile.value.is_available
    profile.value = { ...profile.value, is_available: v }
    try {
      await api('/my/nanny-profile/availability', { method: 'PATCH', body: { is_available: v } })
      ok(v ? t('dashboard.profile.availableOn') : t('dashboard.profile.availableOff'))
    }
    catch (e) {
      profile.value = { ...profile.value!, is_available: old }
      toastError(e)
    }
  },
})

const statusTone = computed(() => ({
  draft: 'border-brand-200! bg-brand-50! dark:border-brand-800! dark:bg-brand-950/50!',
  pending: 'border-honey-200! bg-honey-50! dark:border-honey-800! dark:bg-honey-950/50!',
  active: 'border-leaf-200! bg-leaf-50! dark:border-leaf-800! dark:bg-leaf-950/50!',
  rejected: 'border-pomegranate-200! bg-pomegranate-50! dark:border-pomegranate-800! dark:bg-pomegranate-950/50!',
  hidden: 'border-sand-200! bg-sand-100! dark:border-sand-700! dark:bg-sand-800/50!',
}[profile.value?.status ?? 'draft!']))
const statusIcon = computed(() => ({ draft: 'i-lucide-file-pen-line', pending: 'i-lucide-hourglass', active: 'i-lucide-circle-check', rejected: 'i-lucide-circle-alert', hidden: 'i-lucide-eye-off' }[profile.value?.status ?? 'draft']))
for (const key of Object.keys(form) as (keyof typeof form)[]) {
  watch(() => form[key], () => {
    if (errors.value[key]) { const { [key]: _, ...rest } = errors.value; errors.value = rest }
  }, { deep: true })
}
const err = (k: string) => errors.value[k]
const summaryPlace = computed(() => {
  const r = regions.value.find(x => x.id === form.region_id)
  const d = r?.districts.find(x => x.id === form.district_id)
  return [d, r].filter(Boolean).map(x => localizedName(x!.name)).join(', ')
})
</script>

<template>
  <div id="anketa-top" class="scroll-mt-32">
    <DashboardPageHead :title="t('dashboard.profile.title')" :subtitle="t('dashboard.profile.subtitle')" />

    <DashboardRoleGate role="nanny">
      <template v-if="profile">
        <!-- Holat banneri -->
        <section class="surface mb-6 flex flex-col gap-4 border p-5 sm:flex-row sm:items-center" :class="statusTone">
          <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white/70 text-brand-700 dark:bg-white/10 dark:text-brand-200"><UIcon :name="statusIcon" class="size-6" aria-hidden="true" /></span>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t(`dashboard.profile.banner.${profile.status}.title`) }}</h2>
              <DashboardStatusBadge kind="nanny" :status="profile.status" />
            </div>
            <p class="mt-1 text-muted">{{ t(`dashboard.profile.banner.${profile.status}.text`) }}</p>
          </div>
          <div class="flex flex-col items-start gap-3 sm:items-end">
            <UButton v-if="profile.status === 'active'" color="neutral" variant="outline" icon="i-lucide-external-link" :label="t('dashboard.profile.preview')" :to="localePath(`/enagalar/${profile.id}`)" />
            <USwitch v-if="profile.status === 'active'" v-model="available" :label="t('dashboard.profile.availableLabel')" />
          </div>
        </section>

        <!-- Qadamlar -->
        <ol class="mb-6 grid grid-cols-4 gap-2" :aria-label="t('dashboard.profile.steps')">
          <li v-for="(s, i) in STEPS" :key="s">
            <button
              type="button"
              :aria-current="step === i + 1 ? 'step' : undefined"
              class="flex w-full flex-col items-start gap-2 rounded-2xl border p-3 text-start transition focus-visible:outline-2 focus-visible:outline-primary sm:flex-row sm:items-center sm:gap-3 sm:p-3.5"
              :class="step === i + 1 ? 'border-brand-500 bg-white shadow-sm dark:bg-sand-900' : 'border-default bg-default/60 hover:bg-default'"
              @click="goto(i + 1)"
            >
              <span class="grid size-7 shrink-0 place-items-center rounded-full text-sm font-bold" :class="step > i + 1 ? 'bg-leaf-500 text-white' : step === i + 1 ? 'bg-brand-500 text-white' : 'bg-sand-200 text-sand-700 dark:bg-sand-800 dark:text-sand-200'">
                <UIcon v-if="step > i + 1" name="i-lucide-check" class="size-4" aria-hidden="true" /><template v-else>{{ i + 1 }}</template>
              </span>
              <span class="text-xs font-semibold leading-tight text-highlighted sm:text-sm">{{ t(`dashboard.profile.step.${s}`) }}</span>
            </button>
          </li>
        </ol>

        <div class="mb-4 flex justify-end">
          <DashboardAiPaste type="nanny" @parsed="applyAi" />
        </div>

        <form class="surface space-y-6 p-5 sm:p-8" novalidate @submit.prevent="next">
          <!-- 1 -->
          <template v-if="step === 1">
            <h2 class="h3-title text-highlighted"><AppText :text="t('dashboard.profile.step.basic')" /></h2>
            <div class="grid gap-5 sm:grid-cols-2">
              <UFormField :label="t('search.region')" :error="err('region_id')" required>
                <USelect v-model="form.region_id" :items="regionItems" icon="i-lucide-map-pin" class="w-full" :placeholder="t('dashboard.common.choose')" />
              </UFormField>
              <UFormField :label="t('search.district')" :error="err('district_id')">
                <USelect v-model="form.district_id" :items="districtItems" :disabled="!form.region_id" class="w-full" :placeholder="form.region_id ? t('dashboard.common.choose') : t('search.pickRegionFirst')" />
              </UFormField>
              <UFormField :label="t('dashboard.profile.birthYear')" :error="err('birth_year')" :description="t('dashboard.profile.birthYearHint')">
                <UInput v-model="form.birth_year" inputmode="numeric" maxlength="4" class="w-full" placeholder="1990" @input="form.birth_year = form.birth_year.replace(/\D/g, '')" />
              </UFormField>
              <UFormField :label="t('dashboard.profile.experience')" :error="err('experience_years')" :description="t('dashboard.profile.experienceHint')">
                <UInputNumber v-model="form.experience_years" :min="0" :max="60" class="w-40" />
              </UFormField>
            </div>
          </template>

          <!-- 2 -->
          <template v-else-if="step === 2">
            <h2 class="h3-title text-highlighted"><AppText :text="t('dashboard.profile.step.work')" /></h2>
            <UFormField :label="t('search.workFormat')" :error="err('work_formats')" required>
              <DashboardChipsMulti v-model="form.work_formats" :items="formatItems" :label="t('search.workFormat')" />
            </UFormField>
            <UFormField :label="t('nanny.detail.ageGroups')" :error="err('age_groups')">
              <DashboardChipsMulti v-model="form.age_groups" :items="ageItems" :label="t('nanny.detail.ageGroups')" />
            </UFormField>
            <UFormField :label="t('search.language')" :error="err('languages')" required>
              <DashboardChipsMulti v-model="form.languages" :items="langItems" :label="t('search.language')" />
            </UFormField>
            <UFormField :label="t('search.skills')" :error="err('skills')">
              <DashboardChipsMulti v-model="form.skills" :items="skillItems" :label="t('search.skills')" />
            </UFormField>
            <div class="flex flex-col gap-3 sm:flex-row sm:gap-8">
              <USwitch v-model="form.live_in" :label="t('nanny.detail.liveIn')" />
              <USwitch v-model="form.free_trial_day" :label="t('nanny.detail.freeTrial')" />
            </div>
            <div class="grid max-w-xl gap-5 sm:grid-cols-[1fr_auto]">
              <UFormField :label="t('nanny.detail.price')" :error="err('price_amount')" required>
                <UInput :model-value="spaced(form.price_amount)" inputmode="numeric" class="w-full" :placeholder="t('search.pricePlaceholder')" @input="onPrice" />
              </UFormField>
              <UFormField :label="t('search.unit')" :error="err('price_unit')">
                <USelect v-model="form.price_unit" :items="unitItems" class="w-full sm:w-36" />
              </UFormField>
            </div>
          </template>

          <!-- 3 -->
          <template v-else-if="step === 3">
            <h2 class="h3-title text-highlighted"><AppText :text="t('dashboard.profile.step.about')" /></h2>
            <UFormField :error="err('bio')" :label="t('nanny.detail.about')" required>
              <UTextarea v-model="form.bio" class="w-full" :rows="8" maxlength="3000" :placeholder="t('dashboard.profile.bioPlaceholder')" />
              <template #hint>
                <span :class="bioLen >= 50 ? 'font-semibold text-leaf-700 dark:text-leaf-400' : 'text-muted'" role="status">{{ bioLen }} / 50<template v-if="bioLen >= 50"> ✓</template></span>
              </template>
            </UFormField>
            <p class="text-sm text-muted">{{ t('dashboard.profile.bioHint') }}</p>
          </template>

          <!-- 4 -->
          <template v-else>
            <h2 class="h3-title text-highlighted"><AppText :text="t('dashboard.profile.step.review')" /></h2>
            <dl class="grid gap-x-8 gap-y-4 text-[15px] sm:grid-cols-2">
              <div><dt class="eyebrow text-muted">{{ t('dashboard.vacancyForm.where') }}</dt><dd class="mt-0.5 font-semibold text-highlighted">{{ summaryPlace || '—' }}</dd></div>
              <div><dt class="eyebrow text-muted">{{ t('dashboard.profile.experience') }}</dt><dd class="mt-0.5 font-semibold text-highlighted">{{ plural('format.yearsExp', form.experience_years) }}</dd></div>
              <div><dt class="eyebrow text-muted">{{ t('nanny.detail.price') }}</dt><dd class="mt-0.5 font-semibold text-highlighted"><AppText :text="price(Number(form.price_amount) || null, form.price_unit)" /></dd></div>
              <div><dt class="eyebrow text-muted">{{ t('search.workFormat') }}</dt><dd class="mt-0.5 font-semibold text-highlighted">{{ form.work_formats.map(f => enumLabel('workFormat', f)).join(', ') || '—' }}</dd></div>
              <div><dt class="eyebrow text-muted">{{ t('search.language') }}</dt><dd class="mt-0.5 font-semibold text-highlighted">{{ form.languages.map(f => enumLabel('language', f)).join(', ') || '—' }}</dd></div>
              <div><dt class="eyebrow text-muted">{{ t('search.skills') }}</dt><dd class="mt-0.5 font-semibold text-highlighted">{{ form.skills.map(s => skillItems.find(x => x.value === s)?.label).join(', ') || '—' }}</dd></div>
              <div class="sm:col-span-2"><dt class="eyebrow text-muted">{{ t('nanny.detail.about') }}</dt><dd class="mt-0.5 whitespace-pre-line text-default">{{ form.bio || '—' }}</dd></div>
            </dl>

            <div class="rounded-2xl bg-elevated p-4">
              <h3 class="font-display font-extrabold text-highlighted">{{ t('dashboard.profile.checklist') }}</h3>
              <ul class="mt-3 space-y-2">
                <li v-for="c in checklist" :key="c.key" class="flex items-center gap-2.5 text-[15px]">
                  <UIcon :name="c.done ? 'i-lucide-circle-check' : 'i-lucide-circle'" class="size-5 shrink-0" :class="c.done ? 'text-leaf-600 dark:text-leaf-400' : 'text-dimmed'" aria-hidden="true" />
                  <span :class="c.done ? 'text-default' : 'font-semibold text-highlighted'">{{ t(`dashboard.profile.req.${c.key}`) }}</span>
                  <button v-if="!c.done" type="button" class="ml-auto text-sm font-semibold text-primary underline-offset-2 hover:underline" @click="goto(c.step)">{{ t('dashboard.profile.fix') }}</button>
                  <span v-if="errors[c.key]" class="sr-only">{{ errors[c.key] }}</span>
                </li>
              </ul>
            </div>
            <ul v-if="Object.keys(errors).length" class="space-y-1 rounded-xl bg-pomegranate-50 px-4 py-3 text-sm text-pomegranate-700 dark:bg-pomegranate-950 dark:text-pomegranate-300" role="alert">
              <li v-for="(m, k) in errors" :key="k">{{ m }}</li>
            </ul>
            <p v-if="!canSubmit" class="text-sm text-muted">{{ t('dashboard.profile.alreadySubmitted') }}</p>
          </template>

          <div class="flex flex-col-reverse gap-3 border-t border-default pt-5 sm:flex-row sm:justify-between">
            <UButton v-if="step > 1" type="button" size="lg" color="neutral" variant="outline" class="justify-center" icon="i-lucide-arrow-left" :label="t('dashboard.common.back')" @click="prev" />
            <span v-else />
            <UButton v-if="step < 4" type="submit" size="lg" class="justify-center" trailing-icon="i-lucide-arrow-right" :loading="saving" :label="t('dashboard.profile.saveNext')" />
            <UButton v-else-if="canSubmit" type="button" size="lg" class="justify-center" icon="i-lucide-send" :disabled="!complete" :loading="submitting" :label="t('dashboard.profile.submit')" @click="submit" />
          </div>
        </form>
      </template>
    </DashboardRoleGate>
  </div>
</template>
