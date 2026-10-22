<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
const localizedName = useLocalizedName()
const auth = useAuthStore()

definePageMeta({ middleware: 'auth' })
useSeoMeta({ title: () => t('auth.cabinetSeo'), robots: 'noindex' })

if (auth.user && auth.needsRole) await navigateTo(localePath('/boshlash'))

async function logout() {
  await auth.logout()
  await navigateTo(localePath('/'))
}
</script>

<template>
  <section class="py-10 sm:py-16">
    <UContainer class="max-w-3xl">
      <div v-if="auth.user">
        <p class="eyebrow text-primary">{{ t('auth.cabinetTitle') }}</p>
        <h1 class="h1-title mt-2 text-highlighted">{{ t('auth.hello') }}, {{ auth.user.name || auth.user.telegram_username || t('auth.notSet') }}</h1>

        <div class="surface mt-8 divide-y divide-default">
          <dl class="grid gap-x-6 gap-y-5 p-6 sm:grid-cols-2 sm:p-8">
            <div>
              <dt class="eyebrow text-muted">{{ t('auth.name') }}</dt>
              <dd class="mt-1 text-lg font-semibold text-highlighted">{{ auth.user.name || t('auth.notSet') }}</dd>
            </div>
            <div>
              <dt class="eyebrow text-muted">{{ t('auth.phone') }}</dt>
              <dd class="mt-1 text-lg font-semibold tabular-nums text-highlighted">{{ auth.user.phone || t('auth.notSet') }}</dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="eyebrow text-muted">{{ t('auth.roles') }}</dt>
              <dd class="mt-2 flex flex-wrap gap-2">
                <UBadge v-for="r in auth.user.roles" :key="r" color="primary" variant="subtle" size="lg" :label="t(`auth.role.${r}`, r)" />
              </dd>
            </div>
          </dl>

          <div class="p-6 sm:p-8">
            <h2 class="eyebrow text-muted">{{ t('auth.badges') }}</h2>
            <ul v-if="auth.user.badges.length" class="mt-4 flex flex-wrap gap-2">
              <li v-for="b in auth.user.badges" :key="b.slug">
                <UBadge color="success" variant="subtle" size="lg" :icon="b.icon" :label="localizedName(b.name)" />
              </li>
            </ul>
            <p v-else class="mt-3 text-muted">{{ t('auth.noBadges') }}</p>
          </div>

          <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
            <p class="text-sm text-muted">{{ t('auth.soon') }}</p>
            <UButton color="neutral" variant="outline" icon="i-lucide-log-out" :label="t('nav.logout')" @click="logout" />
          </div>
        </div>
      </div>
    </UContainer>
  </section>
</template>
