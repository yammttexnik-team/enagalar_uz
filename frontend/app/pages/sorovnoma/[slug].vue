<script setup lang="ts">
import { parseApiError } from '~/composables/useDashboard'

interface Question {
  id: string
  type: 'single' | 'multi' | 'text' | 'number' | 'scale'
  text: string
  required: boolean
  options?: { value: string, label: string }[]
  min_length?: number
  min_words?: number
  max_select?: number
  min?: number
  max?: number
}
interface SurveyData {
  slug: string
  audience: 'nanny' | 'parent'
  title: string
  intro: string | null
  bonus: number
  completed: boolean
  status: string | null
  questions: Question[]
}
type Answer = string | string[] | number | null

const { t, locale } = useI18n()
const route = useRoute()
const localePath = useLocalePath()
const auth = useAuthStore()
const api = useApi()
const { money } = useFormat()
const { balance, refreshBalance } = useBonus()
const toast = useToast()

definePageMeta({ middleware: 'auth' })
useSeoMeta({ title: () => t('survey.seo'), robots: 'noindex' })

const slug = String(route.params.slug)
const { data: survey, error, refresh } = await useAsyncData(`survey-${slug}`, () => api<{ data: SurveyData }>(`/surveys/${slug}`, { query: { lang: locale.value } }).then(r => r.data))

// Holat
const step = ref(-1) // -1 = kirish/rozilik, 0..n-1 savollar
const consent = ref(false)
const answers = reactive<Record<string, Answer>>({})
const errors = reactive<Record<string, string>>({})
const submitting = ref(false)
const result = ref<{ status: string, bonus_awarded: number, balance: number } | null>(null)
const alreadyDone = computed(() => !!survey.value?.completed)

const questions = computed(() => survey.value?.questions ?? [])
const current = computed(() => questions.value[step.value])
const total = computed(() => questions.value.length)
const progress = computed(() => (total.value ? Math.round((Math.max(step.value, 0) / total.value) * 100) : 0))

// Qoralamani localStorage'ga avtomatik saqlash (foydalanuvchi bo'yicha)
const draftKey = computed(() => `enagalar:survey:${slug}:${auth.user?.id ?? 0}`)
const hasDraft = ref(false)
onMounted(() => {
  try {
    const raw = localStorage.getItem(draftKey.value)
    if (!raw) return
    const d = JSON.parse(raw) as { answers: Record<string, Answer>, step: number, consent: boolean }
    Object.assign(answers, d.answers ?? {})
    consent.value = !!d.consent
    step.value = Math.min(Math.max(d.step ?? -1, -1), total.value - 1)
    hasDraft.value = Object.keys(d.answers ?? {}).length > 0
  }
  catch { /* buzilgan qoralama eʼtiborsiz */ }
})
watch([answers, step, consent], () => {
  if (result.value || alreadyDone.value) return
  try {
    localStorage.setItem(draftKey.value, JSON.stringify({ answers, step: step.value, consent: consent.value }))
  }
  catch { /* localStorage yoʻq */ }
}, { deep: true })

function isEmpty(v: Answer | undefined) {
  return v === undefined || v === null || v === '' || (Array.isArray(v) && v.length === 0)
}

function validate(q: Question): string {
  const v = answers[q.id]
  if (isEmpty(v)) return q.required ? t('survey.required') : ''
  if (q.type === 'text') {
    const s = String(v).trim()
    if (q.min_length && s.length < q.min_length) return t('survey.minLength', { n: q.min_length })
    const words = s.split(/\s+/).filter(Boolean).length
    if (q.min_words && words < q.min_words) return t('survey.minWords', { n: q.min_words })
  }
  if (q.type === 'number') {
    const n = Number(v)
    if (!Number.isFinite(n) || n < (q.min ?? 0)) return t('survey.invalidNumber')
  }
  if (q.type === 'multi' && q.max_select && (v as string[]).length > q.max_select) return t('survey.maxSelect', { n: q.max_select })
  return ''
}

function next() {
  if (step.value === -1) {
    if (!consent.value) {
      errors._consent = t('survey.intro.consentError')
      return
    }
    delete errors._consent
    step.value = 0
    return
  }
  const q = current.value
  if (!q) return
  const msg = validate(q)
  if (msg) {
    errors[q.id] = msg
    return
  }
  delete errors[q.id]
  if (step.value < total.value - 1) step.value++
  else submit()
}

function back() {
  if (step.value > -1) step.value--
}

function toggleMulti(q: Question, value: string) {
  const list = new Set((answers[q.id] as string[] | undefined) ?? [])
  if (list.has(value)) list.delete(value)
  else if (!q.max_select || list.size < q.max_select) list.add(value)
  answers[q.id] = [...list]
  delete errors[q.id]
}

function setAnswer(q: Question, v: Answer) {
  answers[q.id] = v
  delete errors[q.id]
}

async function submit() {
  // Hamma savolni qayta tekshiramiz (qoralamadan tiklangan boʻlishi mumkin).
  for (const [i, q] of questions.value.entries()) {
    const msg = validate(q)
    if (msg) {
      errors[q.id] = msg
      step.value = i
      return
    }
  }
  submitting.value = true
  try {
    const body: Record<string, Answer> = {}
    for (const q of questions.value) {
      const v = answers[q.id]
      if (isEmpty(v)) continue
      body[q.id] = q.type === 'number' ? Number(v) : q.type === 'text' ? String(v).trim() : v!
    }
    // So'rovnoma faqat o'z auditoriyasi uchun: roli yo'q foydalanuvchiga mos rolni beramiz
    if (survey.value && auth.user && auth.user.roles.length === 0) await auth.addRole(survey.value.audience)
    const res = await api<{ data: { status: string, bonus_awarded: number, balance: number } }>(`/surveys/${slug}/responses`, { method: 'POST', body: { answers: body } })
    result.value = res.data
    balance.value = res.data.balance
    try { localStorage.removeItem(draftKey.value) }
    catch { /* mayli */ }
    refreshBalance()
  }
  catch (e) {
    const p = parseApiError(e)
    if (p.status === 409) {
      await refresh()
      return
    }
    const firstField = Object.keys(p.fields)[0]?.replace(/^answers\./, '').split('.')[0]
    if (p.status === 422 && firstField) {
      const idx = questions.value.findIndex(q => q.id === firstField)
      if (idx >= 0) {
        errors[firstField] = Object.values(p.fields)[0] ?? t('survey.required')
        step.value = idx
      }
    }
    toast.add({ title: ((p.status === 422 && !firstField) || p.status === 403) && p.message ? p.message : p.status === undefined ? t('dashboard.common.offline') : t('survey.error'), color: 'error', icon: 'i-lucide-circle-alert' })
  }
  finally {
    submitting.value = false
  }
}

const ctaTo = computed(() => localePath(survey.value?.audience === 'nanny' ? '/kabinet/anketa' : '/kabinet/vakansiyalar/yangi'))
const ctaLabel = computed(() => t(survey.value?.audience === 'nanny' ? 'survey.success.ctaNanny' : 'survey.success.ctaParent'))
const rangeScale = computed(() => {
  const q = current.value
  const lo = q?.min ?? 1
  const hi = q?.max ?? 5
  return Array.from({ length: hi - lo + 1 }, (_, i) => lo + i)
})
</script>

<template>
  <section class="min-h-[60vh] bg-linear-to-b from-brand-50 to-default py-8 dark:from-brand-950 dark:to-default sm:py-14">
    <UContainer class="max-w-2xl">
      <div v-if="error || !survey" class="surface px-6 py-10 text-center" role="alert">
        <p class="text-muted">{{ t('survey.loadError') }}</p>
        <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('survey.retry')" @click="refresh()" />
      </div>

      <!-- Muvaffaqiyat / allaqachon toʻldirilgan -->
      <div v-else-if="result || alreadyDone" class="surface p-6 text-center sm:p-10" data-testid="survey-success">
        <span class="mx-auto grid size-16 place-items-center rounded-full bg-leaf-500 text-white"><UIcon name="i-lucide-party-popper" class="size-8" aria-hidden="true" /></span>
        <h1 class="h2-title mt-6 text-highlighted"><AppText :text="t('survey.success.title')" /></h1>
        <template v-if="result">
          <p class="mt-3 text-muted">{{ t('survey.success.text') }}</p>
          <div v-if="result.bonus_awarded" class="mx-auto mt-6 max-w-xs rounded-2xl bg-apricot-50 px-5 py-4 dark:bg-apricot-950/40">
            <p class="eyebrow text-apricot-700 dark:text-apricot-300">{{ t('survey.success.earned') }}</p>
            <p class="font-display text-4xl font-black tabular-nums text-highlighted" data-testid="survey-bonus">+{{ money(result.bonus_awarded) }} <span class="text-lg"><AppText :text="t('bonus.som')" /></span></p>
            <p class="mt-1 text-sm text-muted">{{ t('survey.success.balance', { n: money(result.balance) }) }}</p>
          </div>
          <p v-else class="mx-auto mt-6 max-w-md rounded-xl bg-honey-50 px-4 py-3 text-sm text-honey-800 dark:bg-honey-950 dark:text-honey-200">{{ t('survey.success.flagged') }}</p>
        </template>
        <p v-else class="mt-3 text-muted">{{ t('survey.success.already') }}</p>
        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
          <UButton size="xl" class="justify-center" :icon="survey.audience === 'nanny' ? 'i-lucide-file-pen-line' : 'i-lucide-megaphone'" :label="ctaLabel" :to="ctaTo" />
          <UButton size="xl" color="neutral" variant="outline" class="justify-center" icon="i-lucide-gift" :label="t('survey.success.bonuses')" :to="localePath('/kabinet/bonuslar')" />
        </div>
      </div>

      <div v-else class="surface p-5 sm:p-8">
        <!-- Kirish va rozilik -->
        <template v-if="step === -1">
          <p class="eyebrow text-apricot-700 dark:text-apricot-300">{{ t('survey.bonus', { n: money(survey.bonus) }) }}</p>
          <h1 class="h2-title mt-2 text-highlighted"><AppText :text="survey.title" /></h1>
          <p class="mt-3 text-sm text-muted">{{ t('survey.intro.time') }} · {{ t('survey.intro.draft') }}</p>
          <p v-if="survey.intro" class="mt-4 rounded-xl bg-elevated px-4 py-3 text-sm text-default">{{ survey.intro }}</p>
          <UCheckbox v-model="consent" class="mt-5" :label="t('survey.intro.consent')" @update:model-value="delete errors._consent" />
          <p v-if="errors._consent" class="mt-2 text-sm text-pomegranate-600 dark:text-pomegranate-400" role="alert">{{ errors._consent }}</p>
          <UButton class="mt-6 justify-center" block size="xl" icon="i-lucide-arrow-right" trailing :label="hasDraft ? t('survey.continue') : t('survey.start')" @click="next" />
        </template>

        <!-- Savol -->
        <template v-else-if="current">
          <div class="mb-6">
            <div class="mb-2 flex items-center justify-between text-sm text-muted">
              <span class="font-semibold">{{ t('survey.progress', { n: step + 1, total }) }}</span>
              <span class="tabular-nums">{{ progress }}%</span>
            </div>
            <UProgress :model-value="progress" size="sm" :aria-label="t('survey.progress', { n: step + 1, total })" />
          </div>

          <fieldset :key="current.id" class="min-w-0">
            <legend class="font-display text-xl font-extrabold leading-snug text-highlighted">
              <AppText :text="current.text" />
              <span v-if="!current.required" class="ml-1 text-sm font-medium text-muted">({{ t('survey.optional') }})</span>
            </legend>
            <p v-if="current.type === 'multi' && current.max_select" class="mt-1 text-sm text-muted">{{ t('survey.maxHint', { n: current.max_select }) }}</p>

            <div class="mt-5">
              <URadioGroup v-if="current.type === 'single'" :model-value="(answers[current.id] as string | undefined)" :items="current.options?.map(o => ({ label: o.label, value: o.value }))" size="lg" :ui="{ fieldset: 'gap-1' }" @update:model-value="(v: string) => setAnswer(current!, v)" />

              <div v-else-if="current.type === 'multi'" class="grid gap-2">
                <UCheckbox
                  v-for="o in current.options"
                  :key="o.value"
                  size="lg"
                  :label="o.label"
                  :model-value="((answers[current.id] as string[] | undefined) ?? []).includes(o.value)"
                  :disabled="!!current.max_select && !((answers[current.id] as string[] | undefined) ?? []).includes(o.value) && ((answers[current.id] as string[] | undefined) ?? []).length >= current.max_select"
                  @update:model-value="toggleMulti(current!, o.value)"
                />
              </div>

              <UTextarea v-else-if="current.type === 'text'" :model-value="(answers[current.id] as string | undefined) ?? ''" :rows="5" size="xl" class="w-full" :placeholder="t('survey.placeholderText')" :maxlength="2000" @update:model-value="(v: string) => setAnswer(current!, v)" />

              <UInput v-else-if="current.type === 'number'" :model-value="(answers[current.id] as string | undefined) ?? ''" type="number" inputmode="numeric" :min="current.min ?? 0" size="xl" class="w-full" :placeholder="t('survey.placeholderNumber')" @update:model-value="(v: string | number) => setAnswer(current!, String(v))" />

              <div v-else-if="current.type === 'scale'">
                <div class="flex gap-2" role="radiogroup" :aria-label="current.text">
                  <button
                    v-for="n in rangeScale"
                    :key="n"
                    type="button"
                    role="radio"
                    :aria-checked="answers[current.id] === n"
                    class="grid h-14 flex-1 place-items-center rounded-xl border text-lg font-bold transition focus-visible:outline-2 focus-visible:outline-primary"
                    :class="answers[current.id] === n ? 'border-brand-500 bg-brand-500 text-white dark:bg-brand-400 dark:text-sand-900' : 'border-default bg-default text-highlighted hover:bg-elevated'"
                    @click="setAnswer(current!, n)"
                  >{{ n }}</button>
                </div>
                <div class="mt-1.5 flex justify-between text-xs text-muted"><span>{{ rangeScale[0] }} — {{ t('survey.scaleLow') }}</span><span>{{ rangeScale[rangeScale.length - 1] }} — {{ t('survey.scaleHigh') }}</span></div>
              </div>
            </div>
            <p v-if="errors[current.id]" class="mt-3 text-sm font-medium text-pomegranate-600 dark:text-pomegranate-400" role="alert">{{ errors[current.id] }}</p>
          </fieldset>

          <div class="mt-8 flex gap-3">
            <UButton color="neutral" variant="outline" size="lg" icon="i-lucide-arrow-left" :label="t('survey.back')" :disabled="submitting" @click="back" />
            <UButton class="flex-1 justify-center" size="lg" :loading="submitting" :trailing-icon="step === total - 1 ? 'i-lucide-send' : 'i-lucide-arrow-right'" :label="step === total - 1 ? (submitting ? t('survey.submitting') : t('survey.submit')) : t('survey.next')" @click="next" />
          </div>
        </template>
      </div>
    </UContainer>
  </section>
</template>
