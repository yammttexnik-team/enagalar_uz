<script setup lang="ts">
withDefaults(defineProps<{ height?: number }>(), { height: 32 })
</script>

<template>
  <span class="inline-flex shrink-0">
    <img
      src="/brand/logo.svg"
      alt="enagalar.uz"
      :height="height"
      :width="Math.round(height * 5.06)"
      class="dark:hidden w-auto"
      :style="{ height: `${height}px` }"
    >
    <img
      src="/brand/logo-dark.svg"
      alt="enagalar.uz"
      :height="height"
      :width="Math.round(height * 5.06)"
      class="hidden dark:block w-auto"
      :style="{ height: `${height}px` }"
    >
  </span>
</template>
