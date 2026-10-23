<script setup lang="ts">
type Service = 'top' | 'urgent'
interface Prices { top: { price: number, days: number }, urgent: { price: number, days: number } }
interface PromoResult { top_until: string | null, urgent_until: string | null, balance: number }

/** "Ko'proq ko'rinish": TOP va "Shoshilinch" xizmatlarini bonus bilan sotib olish (e'lon yoki anketa egasi uchun). */
const props = defineProps<{
  target: 'vacancy' | 'nanny'
  id: number
  topUntil?: string | null
  urgentUntil?: string | null
  active: boolean
}>()
const emit = defineEmits<{ updated: [{ top_until: string | null, urgent_until: string | null }] }>()

const { t } = useI18n()
const api = useApi()
const localePath = useLocalePath()
const { money, date } = useFormat()
const { toastError, ok } = useApiError()
const { balance, refreshBalance } = useBonus()

// Server javob bermasa ham tugmalar ko'rinsin: marketing rejasidagi standart narxlar.
const { data: prices } = useAsyncData('promo-prices', () => api<{ data: Prices }>('/promotions/prices').then(r => r.data), { lazy: true, server: false })
const list = computed(() => {
  const p = prices.value ?? { top: { price: 25000, days: 7 }, urgent: { price: 10000, days: 3 } }
  return ([
    { key: 'top', icon: 'i-lucide-trending-up', until: props.topUntil, ...p.top },
    { key: 'urgent', icon: 'i-lucide-zap', until: props.urgentUntil, ...p.urgent },
  ] as const).map(s => ({ ...s, isActive: !!s.until && new Date(s.until).getTime() > Date.now() }))
})

onMounted(() => {
  if (balance.value === null) refreshBalance()
})

const chosen = ref<Service | null>(null)
const open = computed({ get: () => chosen.value !== null, set: (v) => { if (!v) chosen.value = null } })
const chosenItem = computed(() => list.value.find(s => s.key === chosen.value))
const loading = ref(false)

const label = (s: Service) => t(`promo.${s}Button`, { days: list.value.find(x => x.key === s)!.days, price: money(list.value.find(x => x.key === s)!.price) })

async function buy() {
  if (!chosen.value) return
  loading.value = true
  try {
    const res = await api<{ data: PromoResult }>('/my/promotions', { method: 'POST', body: { target: props.target, id: props.id, service: chosen.value } })
    balance.value = res.data.balance
    emit('updated', { top_until: res.data.top_until, urgent_until: res.data.urgent_until })
    ok(t(chosen.value === 'top' ? 'promo.doneTop' : 'promo.doneUrgent'))
    chosen.value = null
  }
  catch (e) {
    toastError(e)
  }
  finally { loading.value = false }
}
</script>

<template>
  <section class="surface p-5 sm:p-6" :aria-label="t('promo.title')">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('promo.title') }}</h2>
        <p class="mt-1 max-w-xl text-sm text-muted">{{ t(target === 'vacancy' ? 'promo.subtitleVacancy' : 'promo.subtitleNanny') }}</p>
      </div>
      <NuxtLink :to="localePath('/kabinet/bonuslar')" class="inline-flex items-center gap-1.5 rounded-full bg-honey-100 px-3 py-1.5 text-sm font-bold text-honey-800 dark:bg-honey-900 dark:text-honey-200">
        <UIcon name="i-lucide-gift" class="size-4" aria-hidden="true" />
        {{ t('promo.balance', { n: balance === null ? '…' : money(balance) }) }}
      </NuxtLink>
    </div>

    <p v-if="!active" class="mt-4 flex items-start gap-2 rounded-xl bg-honey-50 px-3 py-2 text-sm text-honey-800 dark:bg-honey-950 dark:text-honey-200">
      <UIcon name="i-lucide-info" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />{{ t(target === 'vacancy' ? 'promo.inactiveVacancy' : 'promo.inactiveNanny') }}
    </p>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
      <div v-for="s in list" :key="s.key" class="flex flex-col gap-2 rounded-2xl border border-default p-4">
        <p class="flex items-center gap-2 font-semibold text-highlighted">
          <UIcon :name="s.icon" class="size-4.5 text-primary" aria-hidden="true" />{{ t(`promo.${s.key}Name`) }}
        </p>
        <p class="text-sm text-muted">{{ t(`promo.${s.key}Text`) }}</p>
        <p v-if="s.isActive" class="text-sm font-semibold text-leaf-700 dark:text-leaf-400">{{ t('promo.activeUntil', { date: date(s.until) }) }}</p>
        <UButton
          class="mt-auto justify-center"
          :color="s.key === 'top' ? 'primary' : 'secondary'"
          :variant="s.isActive ? 'outline' : 'solid'"
          :disabled="!active"
          :label="s.isActive ? t('promo.extend', { label: label(s.key) }) : label(s.key)"
          @click="chosen = s.key"
        />
      </div>
    </div>

    <DashboardConfirm
      v-model:open="open"
      :title="t('promo.confirmTitle')"
      :confirm="t('promo.confirmButton')"
      :loading="loading"
      @confirm="buy"
    >
      <div v-if="chosenItem" class="space-y-2 text-[15px]">
        <p class="text-highlighted">{{ t('promo.confirmText', { service: t(`promo.${chosenItem.key}Name`), days: chosenItem.days, price: money(chosenItem.price) }) }}</p>
        <p v-if="chosenItem.isActive" class="text-sm text-muted">{{ t('promo.confirmExtend', { date: date(chosenItem.until) }) }}</p>
        <p class="text-sm text-muted">{{ t('promo.balance', { n: balance === null ? '…' : money(balance) }) }}</p>
        <p v-if="balance !== null && balance < chosenItem.price" class="text-sm font-semibold text-pomegranate-700 dark:text-pomegranate-300">{{ t('promo.notEnough') }}</p>
      </div>
    </DashboardConfirm>
  </section>
</template>
