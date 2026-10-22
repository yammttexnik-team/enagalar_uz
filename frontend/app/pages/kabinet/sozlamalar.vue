<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
const auth = useAuthStore()
const { toastError } = useApiError()

definePageMeta({ middleware: 'auth', layout: 'kabinet' })
useSeoMeta({ title: () => t('dashboard.settings.seo'), robots: 'noindex' })

const adding = ref<string | null>(null)
const missing = computed(() => (['parent', 'nanny'] as const).filter(r => !auth.user?.roles.includes(r)))
async function add(role: 'parent' | 'nanny') {
  adding.value = role
  try { await auth.addRole(role) }
  catch (e) { toastError(e) }
  finally { adding.value = null }
}
async function logout() {
  await auth.logout()
  await navigateTo(localePath('/'))
}
</script>

<template>
  <div>
    <DashboardPageHead :title="t('dashboard.settings.title')" />
    <div v-if="auth.user" class="space-y-5">
      <section class="surface p-5 sm:p-7">
        <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('dashboard.settings.account') }}</h2>
        <dl class="mt-4 grid gap-x-8 gap-y-4 sm:grid-cols-2">
          <div><dt class="eyebrow text-muted">{{ t('auth.name') }}</dt><dd class="mt-1 font-semibold text-highlighted">{{ auth.user.name || t('auth.notSet') }}</dd></div>
          <div><dt class="eyebrow text-muted">{{ t('auth.phone') }}</dt><dd class="mt-1 font-semibold tabular-nums text-highlighted">{{ auth.user.phone || t('auth.notSet') }}</dd></div>
          <div v-if="auth.user.telegram_username"><dt class="eyebrow text-muted">Telegram</dt><dd class="mt-1 font-semibold text-highlighted">@{{ auth.user.telegram_username }}</dd></div>
          <div>
            <dt class="eyebrow text-muted">{{ t('auth.roles') }}</dt>
            <dd class="mt-1.5 flex flex-wrap gap-2"><UBadge v-for="r in auth.user.roles" :key="r" color="primary" variant="subtle" :label="t(`auth.role.${r}`, r)" /></dd>
          </div>
        </dl>
        <div v-if="missing.length" class="mt-5 flex flex-wrap gap-2 border-t border-default pt-5">
          <UButton v-for="r in missing" :key="r" color="neutral" variant="outline" icon="i-lucide-plus" :loading="adding === r" :label="t(`dashboard.gate.${r}.cta`)" @click="add(r)" />
        </div>
      </section>

      <section class="surface p-5 sm:p-7">
        <h2 class="font-display text-lg font-extrabold text-highlighted">{{ t('dashboard.settings.preferences') }}</h2>
        <div class="mt-4 flex flex-wrap items-center gap-x-8 gap-y-4">
          <div><p class="mb-2 text-sm font-semibold text-highlighted">{{ t('nav.language') }}</p><LayoutLangSwitch /></div>
          <div><p class="mb-2 text-sm font-semibold text-highlighted">{{ t('nav.theme') }}</p><UColorModeButton color="neutral" variant="outline" :aria-label="t('nav.theme')" /></div>
        </div>
      </section>

      <section class="surface p-5 sm:p-7">
        <UButton color="neutral" variant="outline" size="lg" icon="i-lucide-log-out" :label="t('nav.logout')" @click="logout" />
      </section>
    </div>
  </div>
</template>
