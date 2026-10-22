<script setup lang="ts">
const { t, tm, rt } = useI18n()
const localePath = useLocalePath()
const auth = useAuthStore()
const { status, botUrl, start } = useTelegramLogin()

useSeoMeta({ title: () => t('auth.loginSeo'), robots: 'noindex' })

const redirect = safeRedirect(useRoute().query.redirect)
if (auth.isLoggedIn && auth.user) {
  await navigateTo(auth.needsRole ? localePath({ path: '/boshlash', query: redirect ? { redirect } : {} }) : (redirect ?? localePath('/kabinet')))
}

const points = computed(() => (tm('auth.points') as unknown[]).map(p => rt(p as never)))
const loading = computed(() => status.value === 'starting')
const waiting = computed(() => status.value === 'waiting')
</script>

<template>
  <section class="relative overflow-hidden bg-linear-to-b from-brand-50 to-default py-12 dark:from-brand-950 dark:to-default sm:py-20">
    <UContainer class="max-w-lg">
      <div class="surface p-6 text-center sm:p-10">
        <span class="mx-auto grid size-16 place-items-center rounded-full bg-brand-500 text-white">
          <UIcon name="i-lucide-send" class="size-7" aria-hidden="true" />
        </span>

        <template v-if="!waiting">
          <h1 class="h2-title mt-6 text-highlighted">{{ t('auth.loginTitle') }}</h1>
          <p class="mt-3 text-muted">{{ t('auth.loginText') }}</p>

          <p v-if="status === 'expired'" class="mt-5 rounded-xl bg-honey-50 px-4 py-3 text-sm text-honey-800 dark:bg-honey-950 dark:text-honey-200" role="alert">{{ t('auth.expired') }}</p>
          <p v-if="status === 'error'" class="mt-5 rounded-xl bg-pomegranate-50 px-4 py-3 text-sm text-pomegranate-700 dark:bg-pomegranate-950 dark:text-pomegranate-300" role="alert">{{ t('auth.error') }}</p>

          <UButton
            class="mt-7 justify-center"
            block
            size="xl"
            icon="i-lucide-send"
            :loading="loading"
            :label="status === 'expired' || status === 'error' ? t('auth.retry') : loading ? t('auth.starting') : t('auth.loginButton')"
            @click="start"
          />

          <ul class="mt-6 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm font-medium text-default">
            <li v-for="p in points" :key="p" class="inline-flex items-center gap-1.5">
              <UIcon name="i-lucide-check" class="size-4 text-leaf-600 dark:text-leaf-400" aria-hidden="true" />{{ p }}
            </li>
          </ul>
        </template>

        <template v-else>
          <h1 class="h2-title mt-6 text-highlighted">{{ t('auth.waitingTitle') }}</h1>
          <p class="mt-3 text-muted">{{ t('auth.waitingText') }}</p>
          <UButton class="mt-7 justify-center" block size="xl" icon="i-lucide-external-link" :to="botUrl" target="_blank" rel="noopener" :label="t('auth.openBot')" />
          <p class="mt-6 inline-flex items-center gap-2 text-sm text-muted" role="status">
            <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin text-primary" aria-hidden="true" />
            {{ t('auth.waiting') }}
          </p>
        </template>

        <p class="mt-8 text-xs text-muted">{{ t('auth.note') }}</p>
      </div>
    </UContainer>
  </section>
</template>
