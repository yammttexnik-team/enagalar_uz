<script setup lang="ts">
/** Mulish'da oʻ/gʻ belgisi (U+02BB) atrofida boʻshliq katta — ichki siljitish bilan tuzatiladi. */
const props = defineProps<{ text: string }>()
const parts = computed(() => props.text.split(/([ʻʼ])/))
</script>

<template>
  <template v-for="(p, i) in parts" :key="i">
    <span v-if="p === 'ʻ' || p === 'ʼ'" class="apos">{{ p }}</span>
    <template v-else>{{ p }}</template>
  </template>
</template>
