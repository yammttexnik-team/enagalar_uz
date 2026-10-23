<script setup lang="ts">
/** Qidiruv natijasi bo'sh. `compact` — pastda boshqa (Telegram kanallardan) e'lonlar bor: katta blok o'rniga ixcham qator. */
defineProps<{ title: string, text: string, clear: string, post: string, postTo: string, icon?: string, compact?: boolean }>()
defineEmits<{ clear: [] }>()
const authLink = useAuthLink()
</script>

<template>
  <div v-if="compact" class="surface flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
      <UIcon :name="icon || 'i-lucide-search-x'" class="size-5 shrink-0 text-brand-600 dark:text-brand-300" aria-hidden="true" />
      <p class="text-sm text-muted"><span class="font-semibold text-highlighted"><AppText :text="title" /></span> — {{ text }}</p>
    </div>
    <UButton size="sm" icon="i-lucide-megaphone" class="shrink-0 justify-center" :to="authLink(postTo)" :label="post" />
  </div>
  <div v-else class="surface flex flex-col items-center px-6 py-14 text-center">
    <span class="grid size-16 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
      <UIcon :name="icon || 'i-lucide-search-x'" class="size-8" aria-hidden="true" />
    </span>
    <h2 class="h3-title mt-5 text-highlighted"><AppText :text="title" /></h2>
    <p class="mt-2 max-w-md text-muted">{{ text }}</p>
    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
      <UButton size="lg" color="neutral" variant="outline" icon="i-lucide-rotate-ccw" class="justify-center" :label="clear" @click="$emit('clear')" />
      <UButton size="lg" icon="i-lucide-megaphone" class="justify-center" :to="authLink(postTo)" :label="post" />
    </div>
  </div>
</template>
