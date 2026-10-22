<script setup lang="ts">
defineProps<{ label: string }>()
</script>

<template>
  <div class="space-y-2">
    <p class="text-sm font-semibold text-highlighted">{{ label }}</p>
    <slot />
  </div>
</template>
