<script setup lang="ts">
/** Bitta qiymat tanlanadigan chiplar; tanlangan chipni qayta bossa, tanlov bekor boʻladi. */
const props = defineProps<{ items: { label: string, value: string }[], modelValue?: string, label: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: string | undefined] }>()
const toggle = (v: string) => emit('update:modelValue', props.modelValue === v ? undefined : v)
</script>

<template>
  <div role="group" :aria-label="label" class="flex flex-wrap gap-2">
    <button
      v-for="i in items"
      :key="i.value"
      type="button"
      :aria-pressed="modelValue === i.value"
      class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-primary"
      :class="modelValue === i.value
        ? 'border-brand-500 bg-brand-500 text-white dark:border-brand-400 dark:bg-brand-400 dark:text-sand-900'
        : 'border-default bg-default text-default hover:border-brand-300 hover:bg-brand-50 dark:bg-sand-900 dark:hover:bg-brand-950'"
      @click="toggle(i.value)"
    >
      {{ i.label }}
    </button>
  </div>
</template>
