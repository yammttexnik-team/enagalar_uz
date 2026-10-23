<script setup lang="ts">
// Baho tanlash: 1–5 yulduz (radio guruh, klaviatura bilan ham ishlaydi).
const model = defineModel<number>({ default: 0 })
const { t } = useI18n()
const hover = ref(0)
const name = useId()
</script>

<template>
  <fieldset class="inline-flex items-center gap-1" @mouseleave="hover = 0">
    <legend class="sr-only">{{ t('rating.chooseStars') }}</legend>
    <label v-for="i in 5" :key="i" class="cursor-pointer rounded-md p-0.5 has-focus-visible:outline-2 has-focus-visible:outline-primary" @mouseenter="hover = i">
      <input v-model="model" type="radio" class="sr-only" :name="name" :value="i" :aria-label="t('rating.starN', { n: i })">
      <UIcon
        name="i-lucide-star"
        class="size-8 transition"
        :class="i <= (hover || model) ? 'fill-honey-400 text-honey-500' : 'text-sand-300 dark:text-sand-600'"
        aria-hidden="true"
      />
    </label>
  </fieldset>
</template>
