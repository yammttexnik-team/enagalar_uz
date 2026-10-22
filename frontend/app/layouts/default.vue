<script setup lang="ts">
const { locale, locales, setLocale } = useI18n()
const localePath = useLocalePath()

const otherLocale = computed(() => locales.value.find(l => l.code !== locale.value))
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <header class="border-b border-default">
      <UContainer class="flex items-center justify-between h-16">
        <NuxtLink :to="localePath('/')" class="font-bold text-lg text-primary">
          enagalar.uz
        </NuxtLink>
        <div class="flex items-center gap-2">
          <UButton
            v-if="otherLocale"
            variant="ghost"
            color="neutral"
            @click="setLocale(otherLocale.code)"
          >
            {{ otherLocale.name }}
          </UButton>
          <UButton :label="$t('nav.login')" />
        </div>
      </UContainer>
    </header>

    <main class="flex-1">
      <slot />
    </main>

    <footer class="border-t border-default py-6 text-sm text-muted">
      <UContainer>© {{ new Date().getFullYear() }} enagalar.uz — {{ $t('footer.tagline') }}</UContainer>
    </footer>
  </div>
</template>
