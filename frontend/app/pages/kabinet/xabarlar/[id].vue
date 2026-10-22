<script setup lang="ts">
const route = useRoute()
const localePath = useLocalePath()
const id = computed(() => Number(route.params.id))
const valid = computed(() => Number.isInteger(id.value) && id.value > 0)
if (!valid.value) await navigateTo(localePath('/kabinet/xabarlar'), { replace: true })
</script>

<template>
  <ChatThread v-if="valid" :key="id" :conversation-id="id" />
</template>
