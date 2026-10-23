<script setup lang="ts">
// Baho yulduzlari (faqat ko'rsatish). value — 0..5, kasr bo'lsa yaxlitlanadi.
const props = withDefaults(defineProps<{ value: number, size?: 'sm' | 'md' }>(), { size: 'sm' })
const { t } = useI18n()
const filled = computed(() => Math.round(props.value))
</script>

<template>
  <span class="inline-flex items-center gap-0.5" role="img" :aria-label="t('rating.starsAria', { n: value.toFixed(1) })">
    <UIcon
      v-for="i in 5"
      :key="i"
      name="i-lucide-star"
      :class="[size === 'md' ? 'size-5' : 'size-4', i <= filled ? 'fill-honey-400 text-honey-500' : 'text-sand-300 dark:text-sand-600']"
      aria-hidden="true"
    />
  </span>
</template>
