<script setup lang="ts">
/** Moslik bali: halqa (SVG). 0–100. */
const props = defineProps<{ score: number }>()
const r = 22
const c = 2 * Math.PI * r
const offset = computed(() => c * (1 - Math.min(100, Math.max(0, props.score)) / 100))
const tone = computed(() => (props.score >= 75 ? 'text-leaf-600 dark:text-leaf-400' : props.score >= 50 ? 'text-brand-500 dark:text-brand-400' : 'text-apricot-500 dark:text-apricot-400'))
</script>

<template>
  <div class="relative grid size-16 shrink-0 place-items-center" role="img" :aria-label="`${score}%`">
    <svg viewBox="0 0 56 56" class="size-16 -rotate-90" aria-hidden="true">
      <circle cx="28" cy="28" :r="r" fill="none" stroke-width="5" class="stroke-sand-200 dark:stroke-sand-800" />
      <circle cx="28" cy="28" :r="r" fill="none" stroke-width="5" stroke-linecap="round" stroke="currentColor" :class="tone" :stroke-dasharray="c" :stroke-dashoffset="offset" />
    </svg>
    <span class="absolute font-display text-base font-black tabular-nums text-highlighted">{{ score }}</span>
  </div>
</template>
