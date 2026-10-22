<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
const auth = useAuthStore()
const open = ref(false)
const route = useRoute()
watch(() => route.fullPath, () => { open.value = false })

const links = computed(() => [
  { label: t('nav.nannies'), to: localePath({ path: '/', hash: '#parents' }) },
  { label: t('nav.vacancies'), to: localePath({ path: '/', hash: '#nannies' }) },
  { label: t('nav.how'), to: localePath({ path: '/', hash: '#how' }) },
])

async function logout() {
  await auth.logout()
  open.value = false
  await navigateTo(localePath('/'))
}
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-default bg-default/85 backdrop-blur-md">
    <UContainer class="flex h-16 items-center justify-between gap-4">
      <NuxtLink :to="localePath('/')" :aria-label="t('nav.toHome')" class="rounded-lg focus-visible:outline-2 focus-visible:outline-primary">
        <BrandLogo :height="30" />
      </NuxtLink>

      <nav class="hidden md:flex items-center gap-1" :aria-label="t('nav.main')">
        <NuxtLink
          v-for="l in links"
          :key="l.label"
          :to="l.to"
          class="rounded-lg px-3 py-2 text-sm font-medium text-muted hover:text-highlighted hover:bg-elevated transition-colors focus-visible:outline-2 focus-visible:outline-primary"
        >
          {{ l.label }}
        </NuxtLink>
      </nav>

      <div class="hidden md:flex items-center gap-2">
        <LayoutLangSwitch />
        <UColorModeButton color="neutral" variant="ghost" :aria-label="t('nav.theme')" />
        <template v-if="auth.isLoggedIn">
          <UButton :to="localePath('/kabinet')" color="neutral" variant="soft" icon="i-lucide-user-round" :label="t('nav.cabinet')" />
          <UButton color="neutral" variant="ghost" icon="i-lucide-log-out" :aria-label="t('nav.logout')" @click="logout" />
        </template>
        <UButton v-else :to="localePath('/kirish')" :label="t('nav.login')" />
      </div>

      <div class="flex md:hidden items-center gap-1">
        <UColorModeButton color="neutral" variant="ghost" :aria-label="t('nav.theme')" />
        <UButton
          color="neutral"
          variant="ghost"
          icon="i-lucide-menu"
          size="lg"
          :aria-label="t('nav.menu')"
          @click="open = true"
        />
      </div>
    </UContainer>

    <USlideover v-model:open="open" side="right" :title="t('nav.menu')" :close="{ 'aria-label': t('nav.closeMenu') }">
      <template #body>
        <nav class="flex flex-col gap-1" :aria-label="t('nav.mobile')">
          <NuxtLink
            v-for="l in links"
            :key="l.label"
            :to="l.to"
            class="rounded-xl px-3 py-3 text-lg font-semibold text-highlighted hover:bg-elevated"
            @click="open = false"
          >
            {{ l.label }}
          </NuxtLink>
        </nav>
        <div class="mt-6"><LayoutLangSwitch /></div>
      </template>
      <template #footer>
        <div class="flex w-full flex-col gap-2">
          <template v-if="auth.isLoggedIn">
            <UButton :to="localePath('/kabinet')" block size="lg" color="neutral" variant="soft" icon="i-lucide-user-round" :label="t('nav.cabinet')" />
            <UButton block size="lg" color="neutral" variant="ghost" icon="i-lucide-log-out" :label="t('nav.logout')" @click="logout" />
          </template>
          <UButton v-else :to="localePath('/kirish')" block size="lg" :label="t('nav.login')" />
        </div>
      </template>
    </USlideover>
  </header>
</template>
