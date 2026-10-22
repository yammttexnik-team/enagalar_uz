<script setup lang="ts">
defineProps<{ title: string, text: string, clear: string, post: string, icon?: string }>()
defineEmits<{ clear: [] }>()
const localePath = useLocalePath()
</script>

<template>
  <div class="surface flex flex-col items-center px-6 py-14 text-center">
    <span class="grid size-16 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
      <UIcon :name="icon || 'i-lucide-search-x'" class="size-8" aria-hidden="true" />
    </span>
    <h2 class="h3-title mt-5 text-highlighted"><AppText :text="title" /></h2>
    <p class="mt-2 max-w-md text-muted">{{ text }}</p>
    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
      <UButton size="lg" color="neutral" variant="outline" icon="i-lucide-rotate-ccw" class="justify-center" :label="clear" @click="$emit('clear')" />
      <UButton size="lg" icon="i-lucide-megaphone" class="justify-center" :to="localePath('/kirish')" :label="post" />
    </div>
  </div>
</template>
