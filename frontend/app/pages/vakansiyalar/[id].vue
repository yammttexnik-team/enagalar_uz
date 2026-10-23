<script setup lang="ts">
import type { VacancyItem } from '~/components/vacancy/Card.vue'

const { t } = useI18n()
const route = useRoute()
const loginTo = computed(() => localePath({ path: '/kirish', query: { redirect: route.fullPath } }))
const api = useApi()
const auth = useAuthStore()
const toast = useToast()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const config = useRuntimeConfig()
const { salary, enumLabel, relativeTime, date, childAge, plural } = useFormat()

const loadFailed = ref(false)
const notFound = ref(false)
const { data: vacancy, refresh } = await useAsyncData(`vacancy-${route.params.id}`, async () => {
  try {
    loadFailed.value = false
    return (await api<{ data: VacancyItem }>(`/vacancies/${route.params.id}`)).data
  }
  catch (e: unknown) {
    const status = (e as { statusCode?: number }).statusCode
    // Faqat 404 — xato sahifa; boshqa xatolar (tarmoq, 5xx) qayta urinish bilan koʻrsatiladi
    if (status === 404) notFound.value = true
    else loadFailed.value = true
    return null
  }
})
// useAsyncData ichidagi xato sahifaga chiqmaydi, shuning uchun 404 ni shu yerda tashlaymiz
if (notFound.value) throw createError({ statusCode: 404, statusMessage: t('vacancy.detail.notFound'), fatal: true })

const retry = () => refresh()
const v = computed(() => vacancy.value!)
const place = computed(() => [v.value.district, v.value.region].filter(Boolean).map(p => localizedName(p!.name)).join(', '))
const ages = computed(() => v.value.children_ages.map(a => childAge(a)).join(', '))
const salaryText = computed(() => salary(v.value.salary))

const title = computed(() => !vacancy.value ? '' : t('vacancy.detail.seoTitle', { title: v.value.title, place: place.value }))
const description = computed(() => !vacancy.value ? '' : t('vacancy.detail.seoDescription', { salary: salaryText.value, format: enumLabel('workFormat', v.value.work_format), place: place.value }))
const canonical = computed(() => !vacancy.value ? '' : config.public.siteUrl + localePath(`/vakansiyalar/${v.value.id}`))
useSeoMeta({ title, description, ogTitle: title, ogDescription: description, ogUrl: canonical })
useHead({ link: [{ rel: 'canonical', href: canonical }] })

const facts = computed(() => [
  { icon: 'i-lucide-map-pin', label: t('vacancy.detail.location'), value: place.value },
  { icon: 'i-lucide-briefcase', label: t('vacancy.detail.format'), value: enumLabel('workFormat', v.value.work_format) },
  { icon: 'i-lucide-clock', label: t('vacancy.detail.schedule'), value: v.value.schedule },
  { icon: 'i-lucide-baby', label: t('vacancy.detail.children'), value: `${t('vacancy.children', { n: v.value.children_count })}${ages.value ? ` · ${ages.value}` : ''}` },
  { icon: 'i-lucide-languages', label: t('vacancy.detail.languages'), value: v.value.languages.map(l => enumLabel('language', l)).join(', ') },
  { icon: 'i-lucide-calendar-check', label: t('vacancy.detail.startsAt'), value: v.value.starts_at ? date(v.value.starts_at) : null },
  { icon: 'i-lucide-house', label: t('vacancy.detail.liveIn'), value: v.value.live_in ? '✓' : null },
].filter(f => f.value))

// --- Javob berish ---
const open = ref(false)
const message = ref('')
const sending = ref(false)
const applied = ref(false)
const notice = ref<{ text: string, action?: { label: string, to: string } } | null>(null)

function startApply() {
  notice.value = null
  if (!auth.isLoggedIn) return navigateTo(loginTo.value)
  // Rol aniq maʼlum boʻlsa va "nanny" boʻlmasa, modalni ochmaymiz.
  if (auth.user && !auth.user.roles.includes('nanny')) {
    notice.value = { text: t('vacancy.detail.needNanny'), action: { label: t('vacancy.detail.createProfile'), to: localePath('/kabinet/anketa') } }
    return
  }
  open.value = true
}

const { start: startChat, loading: starting } = useStartChat()
function writeParent() {
  notice.value = null
  if (!auth.isLoggedIn) return navigateTo(loginTo.value)
  if (auth.user && !auth.user.roles.includes('nanny')) {
    notice.value = { text: t('vacancy.detail.needNanny'), action: { label: t('vacancy.detail.createProfile'), to: localePath('/kabinet/anketa') } }
    return
  }
  return startChat({ vacancy_id: v.value.id })
}

interface ApiError { statusCode?: number, data?: { message?: string, errors?: Record<string, string[]> } }

async function send() {
  sending.value = true
  try {
    await api(`/vacancies/${v.value.id}/apply`, { method: 'POST', body: { message: message.value.trim() || undefined } })
    applied.value = true
    open.value = false
    toast.add({ title: t('vacancy.detail.success'), icon: 'i-lucide-circle-check', color: 'success' })
  }
  catch (e: unknown) {
    const err = e as ApiError
    const keys = Object.keys(err.data?.errors ?? {})
    if (err.statusCode === 401) {
      auth.clear()
      open.value = false
      await navigateTo(loginTo.value)
      return
    }
    if (err.statusCode === 409) {
      applied.value = true
      open.value = false
      toast.add({ title: t('vacancy.detail.already'), icon: 'i-lucide-info', color: 'info' })
      return
    }
    open.value = false
    if (err.statusCode === 403) {
      notice.value = { text: t('vacancy.detail.needNanny'), action: { label: t('vacancy.detail.createProfile'), to: localePath('/kabinet/anketa') } }
    }
    else if (err.statusCode === 422 && keys.includes('nanny_profile')) {
      notice.value = { text: t('vacancy.detail.inactiveProfile') }
    }
    else if (err.statusCode === 422 && keys.includes('vacancy')) {
      const own = (err.data?.errors?.vacancy?.[0] ?? '').toLowerCase().startsWith('o\'z')
      notice.value = { text: own ? t('vacancy.detail.ownVacancy') : t('vacancy.detail.closed') }
    }
    else {
      notice.value = { text: err.statusCode === undefined ? t('vacancy.detail.offline') : t('vacancy.detail.failed') }
    }
  }
  finally {
    sending.value = false
  }
}
</script>

<template>
  <div v-if="vacancy" class="bg-linear-to-b from-brand-50/60 to-default dark:from-brand-950/60">
    <UContainer class="pb-16 pt-6 sm:pt-8">
      <NuxtLink :to="localePath('/vakansiyalar')" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-highlighted">
        <UIcon name="i-lucide-arrow-left" class="size-4" aria-hidden="true" />{{ t('vacancy.detail.back') }}
      </NuxtLink>

      <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
        <div class="space-y-6">
          <section class="surface p-6 sm:p-8">
            <div v-if="v.is_urgent || v.is_top" class="mb-3 flex flex-wrap gap-1.5">
              <PromoTopChip v-if="v.is_top" />
              <p v-if="v.is_urgent" class="inline-flex items-center gap-1 rounded-full bg-apricot-100 px-2.5 py-1 text-xs font-bold text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200">
                <UIcon name="i-lucide-zap" class="size-3.5" aria-hidden="true" />{{ t('vacancy.urgent') }}
              </p>
            </div>
            <h1 class="h1-title text-highlighted">{{ v.title }}</h1>
            <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-muted">
              <span class="inline-flex items-center gap-1.5"><UIcon name="i-lucide-map-pin" class="size-4" aria-hidden="true" />{{ place }}</span>
              <span>· {{ relativeTime(v.published_at) }}</span>
            </p>
            <div v-if="v.parent" class="mt-5 flex flex-wrap items-center gap-2.5 border-t border-default pt-4 text-sm">
              <span class="text-muted">{{ t('vacancy.detail.from') }}:</span>
              <span class="font-semibold text-highlighted">{{ v.parent.name }}</span>
              <span v-if="v.parent.reviews_count && v.parent.rating" class="inline-flex items-center gap-1 font-semibold text-highlighted">
                <RatingStars :value="v.parent.rating" />{{ v.parent.rating.toFixed(1) }}
                <span class="font-normal text-muted">({{ v.parent.reviews_count }})</span>
              </span>
              <SearchBadgeChip v-for="b in v.parent.badges" :key="b.slug" :badge="b" />
            </div>
          </section>

          <section class="surface p-6 sm:p-8">
            <h2 class="h3-title text-highlighted">{{ t('vacancy.detail.details') }}</h2>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
              <div v-for="f in facts" :key="f.label" class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
                  <UIcon :name="f.icon" class="size-5" aria-hidden="true" />
                </span>
                <div>
                  <dt class="text-xs font-semibold uppercase tracking-wide text-muted">{{ f.label }}</dt>
                  <dd class="mt-0.5 font-medium text-highlighted">{{ f.value }}</dd>
                </div>
              </div>
            </dl>
          </section>

          <section v-if="v.description" class="surface p-6 sm:p-8">
            <h2 class="h3-title text-highlighted">{{ t('vacancy.detail.description') }}</h2>
            <p class="mt-3 whitespace-pre-line text-base leading-relaxed text-default">{{ v.description }}</p>
          </section>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-20">
          <section class="surface p-6">
            <p class="eyebrow text-muted">{{ t('vacancy.detail.salary') }}</p>
            <p class="mt-1.5 font-display text-xl font-black tabular-nums text-highlighted"><AppText :text="salaryText" /></p>
            <UButton
              v-if="!applied"
              block
              size="xl"
              class="mt-5 justify-center"
              icon="i-lucide-send"
              :label="t('vacancy.detail.apply')"
              @click="startApply"
            />
            <p v-else class="mt-5 flex items-center justify-center gap-2 rounded-xl bg-leaf-50 px-4 py-3 font-semibold text-leaf-700 dark:bg-leaf-950 dark:text-leaf-300">
              <UIcon name="i-lucide-circle-check" class="size-5" aria-hidden="true" />{{ t('vacancy.detail.applied') }}
            </p>
            <UButton
              block
              size="xl"
              color="neutral"
              variant="outline"
              class="mt-3 justify-center"
              icon="i-lucide-message-circle"
              :loading="starting"
              :label="t('chat.entry.writeParent')"
              @click="writeParent"
            />
            <div v-if="notice" class="mt-4 rounded-xl bg-honey-50 p-4 text-sm leading-relaxed text-honey-800 dark:bg-honey-950 dark:text-honey-200" role="alert">
              <p>{{ notice.text }}</p>
              <UButton v-if="notice.action" class="mt-3" size="sm" color="neutral" variant="outline" :to="notice.action.to" :label="notice.action.label" />
            </div>
            <div class="mt-4 flex justify-center">
              <SearchFavoriteButton type="vacancy" :id="v.id" label />
            </div>
            <p v-if="v.expires_at" class="mt-4 text-center text-xs text-muted">{{ t('vacancy.detail.expires') }} {{ t('vacancy.detail.expiresTill', { date: date(v.expires_at) }) }}</p>
          </section>

          <section class="rounded-3xl border border-honey-200 bg-honey-50 p-5 dark:border-honey-800 dark:bg-honey-950">
            <p class="flex items-center gap-2 font-semibold text-honey-800 dark:text-honey-200">
              <UIcon name="i-lucide-shield-alert" class="size-5" aria-hidden="true" />{{ t('vacancy.detail.safetyTitle') }}
            </p>
            <p class="mt-2 text-sm leading-relaxed text-honey-800 dark:text-honey-200">{{ t('vacancy.detail.safety') }}</p>
          </section>
        </aside>
      </div>
    </UContainer>

    <UModal v-model:open="open" :title="t('vacancy.detail.modalTitle')" :description="t('vacancy.detail.modalText')">
      <template #body>
        <UFormField :label="t('vacancy.detail.message')" :hint="`${message.length}/1000`">
          <UTextarea v-model="message" :rows="6" :maxlength="1000" autoresize class="w-full" :placeholder="t('vacancy.detail.messagePlaceholder')" />
        </UFormField>
      </template>
      <template #footer>
        <div class="flex w-full justify-end gap-2">
          <UButton color="neutral" variant="outline" :label="t('vacancy.detail.cancel')" @click="open = false" />
          <UButton icon="i-lucide-send" :loading="sending" :label="t('vacancy.detail.send')" @click="send" />
        </div>
      </template>
    </UModal>
  </div>
  <UContainer v-else-if="loadFailed" class="max-w-lg py-20 text-center">
    <p class="text-lg text-muted">{{ t('dashboard.common.error') }}</p>
    <UButton class="mt-6" icon="i-lucide-refresh-cw" :label="t('search.retry')" @click="retry()" />
  </UContainer>
</template>
