<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
const auth = useAuthStore()

definePageMeta({ middleware: 'auth' })
useSeoMeta({ title: () => t('auth.startSeo'), robots: 'noindex' })

const pending = ref<'parent' | 'nanny' | null>(null)
const failed = ref(false)

const roles = computed(() => [
  { value: 'parent' as const, icon: 'i-lucide-house-heart', title: t('auth.parent'), text: t('auth.parentText'), tone: 'brand' },
  { value: 'nanny' as const, icon: 'i-lucide-heart-handshake', title: t('auth.nanny'), text: t('auth.nannyText'), tone: 'apricot' },
])

async function choose(role: 'parent' | 'nanny') {
  pending.value = role
  failed.value = false
  try {
    await auth.addRole(role)
    await navigateTo(localePath('/'))
  }
  catch {
    failed.value = true
  }
  finally {
    pending.value = null
  }
}
</script>

<template>
  <section class="bg-linear-to-b from-brand-50 to-default py-12 dark:from-brand-950 dark:to-default sm:py-20">
    <UContainer class="max-w-3xl">
      <div class="text-center">
        <h1 class="h1-title text-highlighted">{{ t('auth.startTitle') }}</h1>
        <p class="mt-3 text-lg text-muted">{{ t('auth.startText') }}</p>
      </div>
      <p v-if="failed" class="mx-auto mt-6 max-w-md rounded-xl bg-pomegranate-50 px-4 py-3 text-center text-sm text-pomegranate-700 dark:bg-pomegranate-950 dark:text-pomegranate-300" role="alert">{{ t('auth.roleError') }}</p>

      <div class="mt-10 grid gap-5 sm:grid-cols-2">
        <button
          v-for="r in roles"
          :key="r.value"
          type="button"
          :disabled="pending !== null"
          class="surface group flex flex-col items-start gap-4 p-7 text-start transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-60 sm:p-8"
          :class="r.tone === 'apricot' ? 'bg-apricot-50! dark:bg-apricot-950/40!' : ''"
          @click="choose(r.value)"
        >
          <span
            class="grid size-14 place-items-center rounded-2xl"
            :class="r.tone === 'apricot' ? 'bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200' : 'bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200'"
          >
            <UIcon :name="pending === r.value ? 'i-lucide-loader-circle' : r.icon" class="size-7" :class="pending === r.value && 'animate-spin'" aria-hidden="true" />
          </span>
          <span>
            <span class="block font-display text-2xl font-extrabold text-highlighted">{{ r.title }}</span>
            <span class="mt-1.5 block text-base text-muted">{{ r.text }}</span>
          </span>
          <UIcon name="i-lucide-arrow-right" class="mt-auto size-5 text-primary transition group-hover:translate-x-1 rtl:rotate-180" aria-hidden="true" />
        </button>
      </div>
    </UContainer>
  </section>
</template>
