<script setup lang="ts">
const props = defineProps<{ kind: 'vacancy' | 'nanny' | 'application' | 'verification', status: string }>()
const { t, te } = useI18n()

const map: Record<string, 'success' | 'warning' | 'error' | 'neutral' | 'info' | 'primary'> = {
  active: 'success', approved: 'success', shortlisted: 'success', found: 'success',
  pending: 'warning', draft: 'neutral', sent: 'info', viewed: 'primary',
  rejected: 'error', closed: 'neutral', expired: 'neutral', hidden: 'neutral', none: 'neutral',
}
const key = computed(() => `dashboard.status.${props.kind}.${props.status}`)
</script>

<template>
  <UBadge :color="map[status] ?? 'neutral'" variant="subtle" :label="te(key) ? t(key) : status" />
</template>
