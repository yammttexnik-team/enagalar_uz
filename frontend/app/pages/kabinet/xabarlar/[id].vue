<script setup lang="ts">
const route = useRoute()
const id = computed(() => Number(route.params.id))
</script>

<template>
  <ChatThread :key="id" :conversation-id="id" />
</template>
