<script setup lang="ts">
const props = defineProps<{ initials: string, id: number, online?: boolean, size?: 'sm' | 'md' | 'lg' }>()
const tones = [
  'bg-brand-100 text-brand-700 dark:bg-brand-800 dark:text-brand-100',
  'bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-100',
  'bg-leaf-100 text-leaf-700 dark:bg-leaf-900 dark:text-leaf-100',
  'bg-azure-100 text-azure-700 dark:bg-azure-900 dark:text-azure-100',
  'bg-honey-100 text-honey-800 dark:bg-honey-900 dark:text-honey-100',
]
const tone = computed(() => tones[props.id % tones.length])
const { t } = useI18n()
</script>

<template>
  <span class="relative inline-block shrink-0">
    <span
      class="grid place-items-center rounded-full font-display font-black select-none"
      :class="[tone, size === 'sm' ? 'size-10 text-sm' : size === 'lg' ? 'size-14 text-lg' : 'size-12 text-base']"
      aria-hidden="true"
    >{{ initials }}</span>
    <span
      v-if="online"
      class="absolute bottom-0 right-0 size-3.5 rounded-full border-2 border-white bg-leaf-500 dark:border-sand-900 dark:bg-leaf-400"
      role="img"
      :aria-label="t('chat.online')"
    />
  </span>
</template>
