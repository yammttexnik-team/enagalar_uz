<script setup lang="ts">
import type { NannyItem } from '~/components/nanny/Card.vue'
import type { VacancyItem } from '~/components/vacancy/Card.vue'

const { t } = useI18n()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const auth = useAuthStore()
const api = useApi()
const { plural } = useFormat()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('auth.cabinetSeo'), robots: 'noindex' })

if (auth.user && auth.needsRole) await navigateTo(localePath('/boshlash'))

const isParent = computed(() => !!auth.user?.roles.includes('parent'))
const isNanny = computed(() => !!auth.user?.roles.includes('nanny'))

const { data } = await useAsyncData('kabinet-overview', async () => {
  const [verification, vacancies, profile] = await Promise.all([
    api<{ data: VerificationState }>('/my/verification').then(r => r.data).catch(() => null),
    isParent.value ? api<Paginated<OwnVacancy>>('/my/vacancies').catch(() => null) : null,
    isNanny.value ? api<{ data: OwnNanny }>('/my/nanny-profile').then(r => r.data).catch(() => null) : null,
  ])
  return { verification, vacancies, profile }
}, { watch: [isParent, isNanny] })

const verification = computed(() => data.value?.verification)
const vacancies = computed(() => data.value?.vacancies?.data ?? [])
const profile = computed(() => data.value?.profile)

const activeVacancies = computed(() => vacancies.value.filter(v => v.status === 'active').length)
const pendingVacancies = computed(() => vacancies.value.filter(v => v.status === 'pending').length)
const applicationsTotal = computed(() => vacancies.value.reduce((s, v) => s + (v.applications_count ?? 0), 0))

// Enaga uchun mos e'lonlar soni (anketa faol boʻlsa), brauzerda yuklanadi.
const matchCount = ref<number | null>(null)
const myApplications = ref<number | null>(null)
onMounted(async () => {
  if (!isNanny.value) return
  api<Paginated<unknown>>('/my/applications').then(r => (myApplications.value = r.meta.total)).catch(() => {})
  if (profile.value?.status === 'active') {
    api<{ data: unknown[] }>('/my/nanny-profile/matches', { retry: 0 }).then(r => (matchCount.value = r.data.length)).catch(() => {})
  }
})

const { balance } = useBonus()
const { money } = useFormat()
const todoSurvey = ref<{ slug: string, bonus: number } | null>(null)
onMounted(async () => {
  try {
    const list = (await api<{ data: { slug: string, audience: string, bonus: number, completed: boolean }[] }>('/surveys')).data
    const want = isNanny.value ? 'nanny' : 'parent'
    todoSurvey.value = list.find(x => x.audience === want && !x.completed) ?? null
  }
  catch { /* bonus kartasi soʻrovnomasiz ham ishlaydi */ }
})

const verifState = computed(() => verification.value?.identity.status ?? 'none')
const verifTone = computed(() => ({
  approved: 'bg-leaf-50! border-leaf-200! dark:bg-leaf-950/50! dark:border-leaf-800!',
  pending: 'bg-honey-50! border-honey-200! dark:bg-honey-950/50! dark:border-honey-800!',
  rejected: 'bg-pomegranate-50! border-pomegranate-200! dark:bg-pomegranate-950/50! dark:border-pomegranate-800!',
  none: 'bg-brand-50! border-brand-200! dark:bg-brand-950/50! dark:border-brand-800!',
}[verifState.value]))
const verifIcon = computed(() => ({ approved: 'i-lucide-shield-check', pending: 'i-lucide-hourglass', rejected: 'i-lucide-shield-alert', none: 'i-lucide-shield' }[verifState.value]))
const name = computed(() => auth.user?.name || auth.user?.telegram_username || t('auth.notSet'))
const missingRole = computed(() => (isParent.value && isNanny.value ? null : isParent.value ? 'nanny' : 'parent'))
const { toastError } = useApiError()
const adding = ref(false)
async function addMissing() {
  if (!missingRole.value) return
  adding.value = true
  try {
    await auth.addRole(missingRole.value)
  }
  catch (e) { toastError(e) }
  finally { adding.value = false }
}
</script>

<template>
  <div>
    <DashboardPageHead :title="`${t('auth.hello')}, ${name}`" :subtitle="t('dashboard.overview.subtitle')" />

    <!-- Tasdiqlash kartasi -->
    <section class="surface mb-6 flex flex-col gap-4 border p-5 sm:flex-row sm:items-center sm:p-6" :class="verifTone" :aria-label="t('dashboard.nav.verification')">
      <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-white/70 text-brand-700 dark:bg-white/10 dark:text-brand-200">
        <UIcon :name="verifIcon" class="size-7" aria-hidden="true" />
      </span>
      <div class="min-w-0 flex-1">
        <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t(`dashboard.overview.verif.${verifState}.title`) }}</h2>
        <p class="mt-1 text-muted">{{ t(`dashboard.overview.verif.${verifState}.text`) }}</p>
        <ul v-if="auth.user?.badges.length" class="mt-3 flex flex-wrap gap-1.5">
          <li v-for="b in auth.user.badges" :key="b.slug"><SearchBadgeChip :badge="b" /></li>
        </ul>
      </div>
      <UButton
        v-if="verifState !== 'approved'"
        size="lg"
        class="justify-center"
        :icon="verifState === 'pending' ? 'i-lucide-eye' : 'i-lucide-shield-check'"
        :label="verifState === 'pending' ? t('dashboard.overview.verif.view') : t('dashboard.overview.verif.cta')"
        :to="localePath('/kabinet/tasdiqlash')"
      />
      <UButton v-else size="lg" color="neutral" variant="outline" class="justify-center" icon="i-lucide-file-plus-2" :label="t('dashboard.overview.verif.docs')" :to="localePath('/kabinet/tasdiqlash')" />
    </section>

    <!-- Bonus -->
    <section class="surface mb-6 flex flex-col gap-4 bg-apricot-50! p-5 dark:bg-apricot-950/40! sm:flex-row sm:items-center sm:p-6" :aria-label="t('bonus.overview.title')">
      <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-white/70 text-apricot-700 dark:bg-white/10 dark:text-apricot-200"><UIcon name="i-lucide-gift" class="size-7" aria-hidden="true" /></span>
      <div class="min-w-0 flex-1">
        <h2 class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t('bonus.overview.title')" />: <span class="tabular-nums">{{ money(balance ?? 0) }} <AppText :text="t('bonus.som')" /></span></h2>
        <p v-if="todoSurvey" class="mt-1 text-muted">{{ t('bonus.overview.survey', { n: money(todoSurvey.bonus) }) }}</p>
        <p v-else class="mt-1 text-muted">{{ t('bonus.note') }}</p>
      </div>
      <UButton v-if="todoSurvey" size="lg" class="justify-center" icon="i-lucide-clipboard-list" :label="t('bonus.earn.survey.cta')" :to="localePath(`/sorovnoma/${todoSurvey.slug}`)" />
      <UButton v-else size="lg" color="neutral" variant="outline" class="justify-center" icon="i-lucide-gift" :label="t('bonus.overview.cta')" :to="localePath('/kabinet/bonuslar')" />
    </section>

    <!-- Statistika -->
    <section class="mb-6 grid gap-4 sm:grid-cols-3" :aria-label="t('dashboard.overview.stats')">
      <template v-if="isParent">
        <DashboardStat icon="i-lucide-megaphone" :label="t('dashboard.overview.activeVacancies')" :value="activeVacancies" :hint="pendingVacancies ? t('dashboard.overview.pendingHint', { n: pendingVacancies }) : undefined" :to="localePath('/kabinet/vakansiyalar')" />
        <DashboardStat icon="i-lucide-inbox" tone="apricot" :label="t('dashboard.overview.applications')" :value="applicationsTotal" :to="localePath('/kabinet/vakansiyalar')" />
      </template>
      <template v-if="isNanny">
        <DashboardStat icon="i-lucide-file-user" tone="leaf" :label="t('dashboard.overview.profileStatus')" :value="profile ? t(`dashboard.status.nanny.${profile.status}`) : '—'" :to="localePath('/kabinet/anketa')" />
        <DashboardStat icon="i-lucide-sparkles" tone="azure" :label="t('dashboard.overview.matches')" :value="matchCount ?? '—'" :to="localePath('/kabinet/mos-vakansiyalar')" />
        <DashboardStat icon="i-lucide-send" tone="apricot" :label="t('dashboard.overview.myApplications')" :value="myApplications ?? '—'" :to="localePath('/kabinet/javoblarim')" />
      </template>
    </section>

    <!-- Rol kartalari -->
    <section class="grid gap-4 lg:grid-cols-2" :aria-label="t('auth.roles')">
      <article v-if="isParent" class="surface flex flex-col gap-4 p-6">
        <div class="flex items-center gap-3">
          <span class="grid size-12 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-house-heart" class="size-6" aria-hidden="true" /></span>
          <h2 class="font-display text-xl font-extrabold text-highlighted">{{ t('auth.role.parent') }}</h2>
        </div>
        <p class="text-muted">{{ vacancies.length ? t('dashboard.overview.parentHas', { n: data?.vacancies?.meta.total ?? vacancies.length }) : t('dashboard.overview.parentNone') }}</p>
        <div class="mt-auto flex flex-wrap gap-2">
          <UButton icon="i-lucide-plus" :label="t('dashboard.vacancies.new')" :to="localePath('/kabinet/vakansiyalar/yangi')" />
          <UButton color="neutral" variant="outline" icon="i-lucide-list" :label="t('dashboard.nav.vacancies')" :to="localePath('/kabinet/vakansiyalar')" />
          <UButton color="neutral" variant="ghost" icon="i-lucide-search" :label="t('home.hero.ctaNanny')" :to="localePath('/enagalar')" />
        </div>
      </article>

      <article v-if="isNanny" class="surface flex flex-col gap-4 bg-apricot-50! p-6 dark:bg-apricot-950/40!">
        <div class="flex items-center gap-3">
          <span class="grid size-12 place-items-center rounded-2xl bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200"><UIcon name="i-lucide-heart-handshake" class="size-6" aria-hidden="true" /></span>
          <h2 class="font-display text-xl font-extrabold text-highlighted">{{ t('auth.role.nanny') }}</h2>
          <DashboardStatusBadge v-if="profile" kind="nanny" :status="profile.status" class="ml-auto" />
        </div>
        <p class="text-muted">{{ t(`dashboard.overview.nannyText.${profile?.status ?? 'draft'}`) }}</p>
        <div class="mt-auto flex flex-wrap gap-2">
          <UButton color="secondary" icon="i-lucide-file-pen-line" :label="profile?.status === 'draft' || !profile ? t('dashboard.overview.fillProfile') : t('dashboard.overview.editProfile')" :to="localePath('/kabinet/anketa')" />
          <UButton color="neutral" variant="outline" icon="i-lucide-search" :label="t('home.hero.ctaJob')" :to="localePath('/vakansiyalar')" />
        </div>
      </article>

      <article v-if="missingRole" class="surface flex flex-col items-start gap-4 border-dashed p-6">
        <span class="grid size-12 place-items-center rounded-2xl bg-sand-100 text-sand-600 dark:bg-sand-800 dark:text-sand-300">
          <UIcon :name="missingRole === 'nanny' ? 'i-lucide-heart-handshake' : 'i-lucide-house-heart'" class="size-6" aria-hidden="true" />
        </span>
        <div>
          <h2 class="font-display text-lg font-extrabold text-highlighted"><AppText :text="t(`dashboard.overview.add.${missingRole}.title`)" /></h2>
          <p class="mt-1 text-muted">{{ t(`dashboard.overview.add.${missingRole}.text`) }}</p>
        </div>
        <UButton color="neutral" variant="outline" icon="i-lucide-plus" :loading="adding" :label="t(`dashboard.gate.${missingRole}.cta`)" @click="addMissing" />
      </article>
    </section>
  </div>
</template>
