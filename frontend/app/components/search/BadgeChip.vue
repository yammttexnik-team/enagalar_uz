<script setup lang="ts">
import type { Badge } from '~/composables/useApi'

const props = defineProps<{ badge: Badge, iconOnly?: boolean }>()
const localizedName = useLocalizedName()

const tones: Record<string, string> = {
  success: 'bg-leaf-50 text-leaf-700 border-leaf-200 dark:bg-leaf-950 dark:text-leaf-300 dark:border-leaf-800',
  info: 'bg-azure-50 text-azure-700 border-azure-200 dark:bg-azure-950 dark:text-azure-300 dark:border-azure-800',
  primary: 'bg-brand-50 text-brand-700 border-brand-200 dark:bg-brand-950 dark:text-brand-300 dark:border-brand-800',
  warning: 'bg-honey-50 text-honey-800 border-honey-200 dark:bg-honey-950 dark:text-honey-300 dark:border-honey-800',
  neutral: 'bg-sand-100 text-sand-700 border-sand-200 dark:bg-sand-800 dark:text-sand-200 dark:border-sand-700',
}
const tone = computed(() => tones[props.badge.color] ?? tones.neutral)
const name = computed(() => localizedName(props.badge.name))
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-full border text-xs font-semibold"
    :class="[tone, iconOnly ? 'size-7 justify-center' : 'px-2 py-1']"
    :title="iconOnly ? name : undefined"
  >
    <UIcon :name="badge.icon" class="size-3.5 shrink-0" aria-hidden="true" />
    <span v-if="!iconOnly">{{ name }}</span>
    <span v-else class="sr-only">{{ name }}</span>
  </span>
</template>
