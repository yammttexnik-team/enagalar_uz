<script setup lang="ts">
const { t } = useI18n()
const route = useRoute()
const localePath = useLocalePath()
const auth = useAuthStore()
const chat = useChatStore()
const { balance, refreshBalance } = useBonus()
const { money } = useFormat()
onMounted(refreshBalance)
const isThread = computed(() => /\/kabinet\/xabarlar\/\d+/.test(route.path))

const items = computed(() => {
  const roles = auth.user?.roles ?? []
  const list: { to: string, icon: string, label: string, badge?: number }[] = [
    { to: '/kabinet', icon: 'i-lucide-layout-dashboard', label: t('dashboard.nav.overview') },
    { to: '/kabinet/xabarlar', icon: 'i-lucide-messages-square', label: t('dashboard.nav.messages'), badge: chat.unreadTotal },
  ]
  if (roles.includes('parent')) list.push({ to: '/kabinet/vakansiyalar', icon: 'i-lucide-megaphone', label: t('dashboard.nav.vacancies') })
  if (roles.includes('nanny')) {
    list.push(
      { to: '/kabinet/anketa', icon: 'i-lucide-file-user', label: t('dashboard.nav.profile') },
      { to: '/kabinet/mos-vakansiyalar', icon: 'i-lucide-sparkles', label: t('dashboard.nav.matches') },
      { to: '/kabinet/javoblarim', icon: 'i-lucide-send', label: t('dashboard.nav.applications') },
    )
  }
  list.push(
    { to: '/kabinet/tasdiqlash', icon: 'i-lucide-shield-check', label: t('dashboard.nav.verification') },
    { to: '/kabinet/bonuslar', icon: 'i-lucide-gift', label: t('dashboard.nav.bonus') },
    { to: '/kabinet/sevimlilar', icon: 'i-lucide-heart', label: t('dashboard.nav.favorites') },
    { to: '/kabinet/sozlamalar', icon: 'i-lucide-settings', label: t('dashboard.nav.settings') },
  )
  return list.map(i => ({ ...i, href: localePath(i.to) }))
})

const norm = (p: string) => p.replace(/\/$/, '')
const isActive = (href: string) => {
  const path = norm(route.path)
  const h = norm(href)
  return path === h || (!h.endsWith('/kabinet') && path.startsWith(`${h}/`))
}
</script>

<template>
  <div class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-inverted">
      {{ $t("nav.skip") }}
    </a>
    <LayoutAppHeader :class="{ 'max-lg:hidden': isThread }" />

    <!-- Mobil: gorizontal aylanadigan tablar -->
    <nav v-if="!isThread" class="sticky top-16 z-30 border-b border-default bg-default/90 backdrop-blur-md lg:hidden" :aria-label="t('dashboard.nav.label')">
      <ul class="flex gap-1 overflow-x-auto px-4 py-2 [scrollbar-width:none]">
        <li v-for="i in items" :key="i.to" class="shrink-0">
          <NuxtLink
            :to="i.href"
            :aria-current="isActive(i.href) ? 'page' : undefined"
            class="flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-primary"
            :class="isActive(i.href) ? 'bg-brand-500 text-white dark:bg-brand-400 dark:text-sand-900' : 'text-muted hover:bg-elevated hover:text-highlighted'"
          >
            <UIcon :name="i.icon" class="size-4" aria-hidden="true" />{{ i.label }}
            <span v-if="i.to === '/kabinet/bonuslar' && balance" class="rounded-full bg-apricot-500 px-1.5 text-xs font-bold tabular-nums text-white">{{ money(balance) }}</span>
            <span v-if="i.badge" class="grid min-w-5 place-items-center rounded-full bg-apricot-500 px-1.5 text-xs font-bold text-white" :aria-label="t('chat.unreadCount', { n: i.badge })">{{ i.badge > 99 ? '99+' : i.badge }}</span>
          </NuxtLink>
        </li>
      </ul>
    </nav>

    <div class="flex-1 bg-linear-to-b from-brand-50/60 to-default dark:from-brand-950/40">
      <UContainer class="grid gap-8 py-6 lg:grid-cols-[250px_minmax(0,1fr)] lg:py-10">
        <aside class="hidden lg:block" :aria-label="t('dashboard.nav.label')">
          <div class="surface sticky top-24 p-3">
            <div v-if="auth.user" class="mb-2 flex items-center gap-3 border-b border-default px-3 pb-4 pt-3">
              <NannyAvatar :name="auth.user.name || auth.user.telegram_username || '?'" :id="auth.user.id" />
              <div class="min-w-0">
                <p class="truncate font-display font-extrabold text-highlighted">{{ auth.user.name || auth.user.telegram_username || t('auth.notSet') }}</p>
                <p class="truncate text-sm text-muted">{{ auth.user.roles.map(r => t(`auth.role.${r}`, r)).join(' · ') }}</p>
                <NuxtLink v-if="balance !== null" :to="localePath('/kabinet/bonuslar')" class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-apricot-100 px-2.5 py-0.5 text-xs font-bold tabular-nums text-apricot-800 dark:bg-apricot-900 dark:text-apricot-100" :aria-label="t('bonus.chip', { n: money(balance) })" data-testid="bonus-chip">
                  <UIcon name="i-lucide-gift" class="size-3.5" aria-hidden="true" />{{ money(balance) }} {{ t('bonus.som') }}
                </NuxtLink>
              </div>
            </div>
            <ul class="space-y-0.5">
              <li v-for="i in items" :key="i.to">
                <NuxtLink
                  :to="i.href"
                  :aria-current="isActive(i.href) ? 'page' : undefined"
                  class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-primary"
                  :class="isActive(i.href) ? 'bg-brand-50 text-brand-700 dark:bg-brand-900 dark:text-brand-100' : 'text-muted hover:bg-elevated hover:text-highlighted'"
                >
                  <UIcon :name="i.icon" class="size-5" aria-hidden="true" />{{ i.label }}
                  <span v-if="i.badge" class="ml-auto grid min-w-5 place-items-center rounded-full bg-brand-500 px-1.5 py-0.5 text-xs font-bold text-white dark:bg-brand-400 dark:text-sand-900" :aria-label="t('chat.unreadCount', { n: i.badge })">{{ i.badge > 99 ? '99+' : i.badge }}</span>
                </NuxtLink>
              </li>
            </ul>
          </div>
        </aside>

        <main id="main" class="min-w-0">
          <slot />
        </main>
      </UContainer>
    </div>
    <LayoutAppFooter :class="{ 'max-lg:hidden': isThread }" />
  </div>
</template>
