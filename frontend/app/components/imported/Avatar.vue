<script setup lang="ts">
/** Telegram e'loni uchun ixcham avatar: enaga — brend rangida, vakansiya — o'rik/qum rangida (ikki tur farqlanadi). */
defineProps<{ type: 'vacancy' | 'nanny' }>()
</script>

<template>
  <span
    class="grid size-11 shrink-0 place-items-center rounded-full"
    :class="type === 'nanny'
      ? 'bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200'
      : 'bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200'"
    aria-hidden="true"
  >
    <UIcon :name="type === 'nanny' ? 'i-lucide-heart-handshake' : 'i-lucide-house'" class="size-5.5" />
  </span>
</template>
