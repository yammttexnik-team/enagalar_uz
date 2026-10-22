<script setup lang="ts">
defineProps<{ title: string, subtitle?: string, back?: { to: string, label: string } }>()
</script>

<template>
  <header class="mb-6">
    <NuxtLink v-if="back" :to="back.to" class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-highlighted">
      <UIcon name="i-lucide-arrow-left" class="size-4 rtl:rotate-180" aria-hidden="true" />{{ back.label }}
    </NuxtLink>
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
      <div class="min-w-0">
        <h1 class="h2-title text-highlighted"><AppText :text="title" /></h1>
        <p v-if="subtitle" class="mt-1.5 max-w-2xl text-muted">{{ subtitle }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2"><slot name="actions" /></div>
    </div>
  </header>
</template>
