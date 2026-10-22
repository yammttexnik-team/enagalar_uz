<script setup lang="ts">
const props = defineProps<{ type: 'nanny' | 'vacancy', id: number, label?: boolean }>()
const { t } = useI18n()
const fav = useFavorites()
const active = computed(() => fav.has(props.type, props.id))
onMounted(() => fav.load())
const text = computed(() => (active.value ? t('search.favoriteRemove') : t('search.favoriteAdd')))
</script>

<template>
  <UButton
    color="neutral"
    :variant="label ? 'outline' : 'ghost'"
    :icon="active ? 'i-lucide-heart' : 'i-lucide-heart'"
    :aria-label="text"
    :aria-pressed="active"
    :label="label ? text : undefined"
    :class="active ? '[&_svg]:fill-pomegranate-500 [&_svg]:text-pomegranate-500 dark:[&_svg]:fill-pomegranate-400 dark:[&_svg]:text-pomegranate-400' : ''"
    :title="text"
    @click.prevent.stop="fav.toggle(type, id)"
  />
</template>
