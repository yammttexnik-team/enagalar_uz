<script setup lang="ts">
/** Mulish'da oʻ/gʻ belgisi (U+02BB) atrofida boʻshliq katta — ichki siljitish bilan tuzatiladi. */
const props = defineProps<{ text: string }>()
const parts = computed(() => props.text.split(/(ʻ)/))
</script>

<template>
  <template v-for="(p, i) in parts" :key="i">
    <span v-if="p === 'ʻ'" class="apos">ʻ</span>
    <template v-else>{{ p }}</template>
  </template>
</template>
