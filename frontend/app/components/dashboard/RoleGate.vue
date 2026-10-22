<script setup lang="ts">
const props = defineProps<{ role: 'parent' | 'nanny' }>()
const auth = useAuthStore()
const { t } = useI18n()
const { toastError } = useApiError()
const busy = ref(false)
const has = computed(() => !!auth.user?.roles.includes(props.role))

async function add() {
  busy.value = true
  try {
    await auth.addRole(props.role)
  }
  catch (e) {
    toastError(e)
  }
  finally {
    busy.value = false
  }
}
</script>

<template>
  <slot v-if="has" />
  <div v-else class="surface flex flex-col items-start gap-4 p-6 sm:p-8">
    <span class="grid size-14 place-items-center rounded-2xl bg-apricot-100 text-apricot-700 dark:bg-apricot-900 dark:text-apricot-200">
      <UIcon :name="role === 'nanny' ? 'i-lucide-heart-handshake' : 'i-lucide-house-heart'" class="size-7" aria-hidden="true" />
    </span>
    <div>
      <h2 class="h3-title text-highlighted"><AppText :text="t(`dashboard.gate.${role}.title`)" /></h2>
      <p class="mt-1.5 max-w-xl text-muted">{{ t(`dashboard.gate.${role}.text`) }}</p>
    </div>
    <UButton size="lg" :loading="busy" :icon="role === 'nanny' ? 'i-lucide-heart-handshake' : 'i-lucide-house-heart'" :label="t(`dashboard.gate.${role}.cta`)" @click="add" />
  </div>
</template>
