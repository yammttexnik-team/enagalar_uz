<script lang="ts">
let active: HTMLAudioElement | null = null
</script>

<script setup lang="ts">
const props = defineProps<{ src: string, duration?: number, mine?: boolean }>()
const { t } = useI18n()
const { duration: fmt } = useChatFormat()
const audio = ref<HTMLAudioElement>()
const playing = ref(false)
const current = ref(0)
const total = ref(props.duration ?? 0)
const failed = ref(false)

const length = computed(() => props.duration || (Number.isFinite(total.value) ? total.value : 0))
const progress = computed(() => (length.value ? Math.min(100, (current.value / length.value) * 100) : 0))

async function toggle() {
  const a = audio.value
  if (!a) return
  if (playing.value) { a.pause(); return }
  if (active && active !== a) active.pause()
  active = a
  try { await a.play(); failed.value = false }
  catch { failed.value = true }
}

function seek(e: MouseEvent | KeyboardEvent) {
  const a = audio.value
  if (!a || !length.value) return
  if ('key' in e) {
    if (e.key === 'ArrowRight') a.currentTime = Math.min(length.value, a.currentTime + 5)
    else if (e.key === 'ArrowLeft') a.currentTime = Math.max(0, a.currentTime - 5)
    else return
    e.preventDefault()
    return
  }
  const r = (e.currentTarget as HTMLElement).getBoundingClientRect()
  a.currentTime = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)) * length.value
}

onBeforeUnmount(() => { audio.value?.pause(); if (active === audio.value) active = null })
</script>

<template>
  <div class="flex w-56 items-center gap-3 py-0.5 sm:w-64">
    <audio
      ref="audio"
      :src="src"
      preload="metadata"
      @play="playing = true"
      @pause="playing = false"
      @ended="playing = false; current = 0"
      @timeupdate="current = audio!.currentTime"
      @loadedmetadata="Number.isFinite(audio!.duration) && (total = audio!.duration)"
      @error="failed = true"
    />
    <button
      type="button"
      class="grid size-10 shrink-0 place-items-center rounded-full transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
      :class="mine ? 'bg-white text-brand-600 hover:bg-brand-50 focus-visible:outline-white' : 'bg-brand-500 text-white hover:bg-brand-600 focus-visible:outline-primary'"
      :aria-label="playing ? t('chat.pause') : t('chat.play')"
      @click="toggle"
    >
      <UIcon :name="playing ? 'i-lucide-pause' : 'i-lucide-play'" class="size-5" aria-hidden="true" />
    </button>
    <div class="min-w-0 flex-1">
      <div
        class="relative h-6 cursor-pointer touch-none rounded focus-visible:outline-2"
        role="slider"
        tabindex="0"
        :aria-label="t('chat.seek')"
        aria-valuemin="0"
        :aria-valuemax="Math.round(length)"
        :aria-valuenow="Math.round(current)"
        @click="seek"
        @keydown="seek"
      >
        <div class="absolute inset-x-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full" :class="mine ? 'bg-white/35' : 'bg-sand-200 dark:bg-sand-700'" />
        <div class="absolute left-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full" :class="mine ? 'bg-white' : 'bg-brand-500'" :style="{ width: `${progress}%` }" />
        <div class="absolute top-1/2 size-3 -translate-x-1/2 -translate-y-1/2 rounded-full" :class="mine ? 'bg-white' : 'bg-brand-500'" :style="{ left: `${progress}%` }" />
      </div>
      <p class="text-xs tabular-nums" :class="mine ? 'text-white/85' : 'text-muted'">
        <template v-if="failed">{{ t('chat.voiceFailed') }}</template>
        <template v-else>{{ fmt(playing || current > 0 ? current : length) }}<span v-if="length && (playing || current > 0)"> / {{ fmt(length) }}</span></template>
      </p>
    </div>
  </div>
</template>
