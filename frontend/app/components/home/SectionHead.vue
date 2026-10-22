<script setup lang="ts">
defineProps<{ eyebrow: string, title: string, subtitle?: string, center?: boolean }>()
</script>

<template>
  <div :class="center ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl'">
    <p class="eyebrow text-primary">{{ eyebrow }}</p>
    <h2 class="h1-title mt-3 text-highlighted"><AppText :text="title" /></h2>
    <p v-if="subtitle" class="mt-4 text-lg leading-relaxed text-muted">{{ subtitle }}</p>
  </div>
</template>
