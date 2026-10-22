<script setup lang="ts">
const NuxtLink = resolveComponent('NuxtLink')
defineProps<{ icon: string, label: string, value: string | number, hint?: string, tone?: 'brand' | 'apricot' | 'leaf' | 'azure', to?: string }>()
const tones = {
  brand: 'bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200',
  apricot: 'bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200',
  leaf: 'bg-leaf-100 text-leaf-700 dark:bg-leaf-900 dark:text-leaf-200',
  azure: 'bg-azure-100 text-azure-700 dark:bg-azure-900 dark:text-azure-200',
}
</script>

<template>
  <component :is="to ? NuxtLink : 'div'" :to="to" class="surface relative flex items-center gap-4 p-5 transition" :class="to && 'hover:-translate-y-0.5 hover:shadow-[var(--shadow-lift)]'">
    <span class="grid size-12 shrink-0 place-items-center rounded-2xl" :class="tones[tone || 'brand']">
      <UIcon :name="icon" class="size-6" aria-hidden="true" />
    </span>
    <div class="min-w-0">
      <p class="font-display text-2xl font-black tabular-nums leading-none text-highlighted">{{ value }}</p>
      <p class="mt-1.5 text-sm text-muted">{{ label }}</p>
      <p v-if="hint" class="text-xs text-dimmed">{{ hint }}</p>
    </div>
  </component>
</template>
