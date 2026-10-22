<script setup lang="ts">
const props = defineProps<{ name: string, id: number, size?: 'md' | 'xl' }>()
const { initials } = useFormat()

const tones = [
  'bg-brand-100 text-brand-700 dark:bg-brand-800 dark:text-brand-100',
  'bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-100',
  'bg-leaf-100 text-leaf-700 dark:bg-leaf-900 dark:text-leaf-100',
  'bg-azure-100 text-azure-700 dark:bg-azure-900 dark:text-azure-100',
  'bg-honey-100 text-honey-800 dark:bg-honey-900 dark:text-honey-100',
]
const tone = computed(() => tones[props.id % tones.length])
</script>

<template>
  <span
    class="grid shrink-0 place-items-center rounded-full font-display font-black select-none"
    :class="[tone, size === 'xl' ? 'size-24 text-3xl sm:size-28 sm:text-4xl' : 'size-14 text-lg']"
    aria-hidden="true"
  >{{ initials(name) }}</span>
</template>
