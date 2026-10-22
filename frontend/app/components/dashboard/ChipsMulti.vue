<script setup lang="ts">
/** Koʻp qiymat tanlanadigan chiplar (anketa va e'lon formalari uchun). */
const props = defineProps<{ items: { label: string, value: string }[], modelValue: string[], label: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>()
const toggle = (v: string) => emit('update:modelValue', props.modelValue.includes(v) ? props.modelValue.filter(x => x !== v) : [...props.modelValue, v])
</script>

<template>
  <div role="group" :aria-label="label" class="flex flex-wrap gap-2">
    <button
      v-for="i in items"
      :key="i.value"
      type="button"
      :aria-pressed="modelValue.includes(i.value)"
      class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-primary"
      :class="modelValue.includes(i.value)
        ? 'border-brand-500 bg-brand-500 text-white dark:border-brand-400 dark:bg-brand-400 dark:text-sand-900'
        : 'border-default bg-default text-default hover:border-brand-300 hover:bg-brand-50 dark:bg-sand-900 dark:hover:bg-brand-950'"
      @click="toggle(i.value)"
    >
      <UIcon v-if="modelValue.includes(i.value)" name="i-lucide-check" class="size-3.5" aria-hidden="true" />{{ i.label }}
    </button>
  </div>
</template>
