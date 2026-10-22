<script setup lang="ts">
const props = defineProps<{ accept: string[], maxMb: number, label: string, hint?: string, error?: string, inputId: string }>()
const file = defineModel<File | null>({ default: null })
const { t, locale } = useI18n()
const localError = ref('')
const preview = ref<string | null>(null)
const input = ref<HTMLInputElement>()

function setPreview(f: File | null) {
  if (preview.value) URL.revokeObjectURL(preview.value)
  preview.value = f && f.type.startsWith('image/') ? URL.createObjectURL(f) : null
}
function onChange(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0] ?? null
  localError.value = ''
  if (f && !props.accept.includes(f.type)) {
    localError.value = t('dashboard.file.badType')
    file.value = null
    setPreview(null)
    if (input.value) input.value.value = ''
    return
  }
  if (f && f.size > props.maxMb * 1024 * 1024) {
    localError.value = t('dashboard.file.tooBig', { n: props.maxMb })
    file.value = null
    setPreview(null)
    if (input.value) input.value.value = ''
    return
  }
  file.value = f
  setPreview(f)
}
function clear() {
  file.value = null
  setPreview(null)
  localError.value = ''
  if (input.value) input.value.value = ''
}
watch(file, (f) => { if (!f) setPreview(null) })
onBeforeUnmount(() => setPreview(null))
const size = computed(() => {
  if (!file.value) return ''
  const mb = file.value.size >= 1024 * 1024
  return new Intl.NumberFormat(locale.value, { style: 'unit', unit: mb ? 'megabyte' : 'kilobyte', unitDisplay: 'short', maximumFractionDigits: mb ? 1 : 0 })
    .format(mb ? file.value.size / 1024 / 1024 : Math.max(1, Math.round(file.value.size / 1024)))
})
const shownError = computed(() => localError.value || props.error)
</script>

<template>
  <div>
    <input :id="inputId" ref="input" type="file" class="sr-only" :accept="accept.join(',')" :aria-describedby="`${inputId}-hint`" @change="onChange">
    <div
      v-if="!file"
      class="flex flex-col items-center gap-3 rounded-2xl border-2 border-dashed px-4 py-8 text-center transition"
      :class="shownError ? 'border-pomegranate-400 bg-pomegranate-50/50 dark:bg-pomegranate-950/30' : 'border-sand-300 bg-sand-50 dark:border-sand-700 dark:bg-sand-900'"
    >
      <span class="grid size-12 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-image-up" class="size-6" aria-hidden="true" /></span>
      <label :for="inputId" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 focus-within:outline-2 has-[:focus-visible]:outline-primary">{{ label }}</label>
      <p :id="`${inputId}-hint`" class="max-w-xs text-sm text-muted">{{ hint }}</p>
    </div>
    <div v-else class="flex items-center gap-4 rounded-2xl border border-default bg-default p-3">
      <img v-if="preview" :src="preview" :alt="t('dashboard.file.previewAlt')" class="size-24 shrink-0 rounded-xl object-cover sm:size-28">
      <span v-else class="grid size-24 shrink-0 place-items-center rounded-xl bg-sand-100 text-sand-600 dark:bg-sand-800 dark:text-sand-300 sm:size-28"><UIcon name="i-lucide-file-text" class="size-10" aria-hidden="true" /></span>
      <div class="min-w-0 flex-1">
        <p class="truncate font-semibold text-highlighted">{{ file.name }}</p>
        <p class="text-sm text-muted">{{ size }}</p>
        <div class="mt-2 flex flex-wrap gap-2">
          <UButton size="xs" color="neutral" variant="outline" icon="i-lucide-replace" :label="t('dashboard.file.change')" @click="input?.click()" />
          <UButton size="xs" color="error" variant="ghost" icon="i-lucide-trash-2" :label="t('dashboard.file.remove')" @click="clear" />
        </div>
      </div>
    </div>
    <p v-if="shownError" class="mt-1.5 text-sm text-pomegranate-600 dark:text-pomegranate-400" role="alert">{{ shownError }}</p>
  </div>
</template>
