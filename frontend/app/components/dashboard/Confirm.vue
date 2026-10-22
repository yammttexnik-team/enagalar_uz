<script setup lang="ts">
defineProps<{ title: string, text?: string, confirm: string, color?: 'error' | 'primary' | 'neutral', loading?: boolean }>()
const open = defineModel<boolean>('open', { required: true })
const emit = defineEmits<{ confirm: [] }>()
const { t } = useI18n()
</script>

<template>
  <UModal v-model:open="open" :title="title" :description="text" :close="{ 'aria-label': t('search.close') }">
    <template #body>
      <slot />
    </template>
    <template #footer>
      <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <UButton color="neutral" variant="outline" class="justify-center" :label="t('dashboard.common.cancel')" :disabled="loading" @click="open = false" />
        <UButton :color="color || 'primary'" class="justify-center" :loading="loading" :label="confirm" @click="emit('confirm')" />
      </div>
    </template>
  </UModal>
</template>
