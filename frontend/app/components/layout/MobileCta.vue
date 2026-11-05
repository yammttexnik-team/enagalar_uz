<script setup lang="ts">
const { t } = useI18n()
const localePath = useLocalePath()
const authLink = useAuthLink()
const auth = useAuthStore()
const route = useRoute()

// Faqat mehmonlarga; kirish va kabinet sahifalarida koʻrsatilmaydi
const show = computed(() => !auth.isLoggedIn && !/\/(kirish|kabinet)(\/|$)/.test(route.path))
</script>

<template>
  <template v-if="show">
    <div class="h-24 md:hidden" aria-hidden="true" />
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-default bg-default/95 px-4 pt-3 backdrop-blur-md md:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
      <div class="mx-auto grid max-w-md grid-cols-2 gap-2.5">
        <UButton block size="lg" color="secondary" icon="i-lucide-plus" class="justify-center" :to="authLink('/kabinet/vakansiyalar/yangi')" :label="t('nav.post')" />
        <UButton block size="lg" color="neutral" variant="outline" icon="i-lucide-user-round-plus" class="justify-center" :to="authLink('/kabinet/anketa')" :label="t('nav.createProfile')" />
      </div>
    </div>
  </template>
</template>
