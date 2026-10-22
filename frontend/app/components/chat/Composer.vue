<script setup lang="ts">
const props = defineProps<{ conversationId: number, replyTo: ChatMessage | null, editing: ChatMessage | null, replyAuthor?: string }>()
const emit = defineEmits<{ cancelReply: [], cancelEdit: [], typing: [], sent: [] }>()
const { t } = useI18n()
const chat = useChatStore()
const toast = useToast()
const { toastError } = useApiError()
const { duration: fmt } = useChatFormat()
const previewText = useChatPreview()

const text = ref('')
const input = ref<HTMLTextAreaElement>()
const fileInput = ref<HTMLInputElement>()
const image = ref<{ file: File, url: string } | null>(null)
const saving = ref(false)
const coarse = import.meta.client && window.matchMedia('(pointer: coarse)').matches

const voice = useVoiceRecorder((blob, secs, ext) => {
  chat.send(props.conversationId, { type: 'voice', file: blob, fileName: `voice.${ext}`, duration: secs, replyTo: props.replyTo })
  emit('cancelReply')
  emit('sent')
})

function resize() {
  const el = input.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = `${Math.min(el.scrollHeight, 144)}px`
}
watch(text, () => nextTick(resize))

watch(() => props.editing, (m) => {
  if (m) { text.value = m.body ?? ''; clearImage(); nextTick(() => input.value?.focus()) }
})
watch(() => props.replyTo, (m) => { if (m) input.value?.focus() })

function clearImage() {
  if (image.value) URL.revokeObjectURL(image.value.url)
  image.value = null
}
onBeforeUnmount(clearImage)

function pick(e: Event) {
  const el = e.target as HTMLInputElement
  const file = el.files?.[0]
  el.value = ''
  if (!file) return
  if (!IMAGE_TYPES.includes(file.type)) return void toast.add({ title: t('chat.imageType'), color: 'warning', icon: 'i-lucide-image-off' })
  if (file.size > MAX_IMAGE_BYTES) return void toast.add({ title: t('chat.imageSize'), color: 'warning', icon: 'i-lucide-image-off' })
  clearImage()
  image.value = { file, url: URL.createObjectURL(file) }
  input.value?.focus()
}

const canSend = computed(() => !!text.value.trim() || !!image.value)

async function submit() {
  const body = text.value.trim()
  if (props.editing) {
    if (!body) return
    if (body === props.editing.body) return emit('cancelEdit')
    saving.value = true
    try {
      await chat.edit(props.editing, body)
      text.value = ''
      emit('cancelEdit')
    }
    catch (e) { toastError(e, t('chat.editFailed')) }
    finally { saving.value = false }
    return
  }
  if (!canSend.value) return
  const img = image.value
  chat.send(props.conversationId, img
    ? { type: 'image', file: img.file, fileName: img.file.name, body: body || undefined, replyTo: props.replyTo }
    : { type: 'text', body, replyTo: props.replyTo })
  text.value = ''
  image.value = null // objectURL'ni optimistik xabar oʻzi tozalaydi (yangi URL yaratiladi)
  if (img) URL.revokeObjectURL(img.url)
  emit('cancelReply')
  emit('sent')
  nextTick(() => { resize(); input.value?.focus() })
}

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (props.editing) { text.value = ''; emit('cancelEdit') }
    else if (props.replyTo) emit('cancelReply')
    return
  }
  if (e.key === 'Enter' && !e.shiftKey && !coarse && !e.isComposing) {
    e.preventDefault()
    submit()
  }
}

function onInput() {
  if (text.value.trim() && !props.editing) emit('typing')
}

defineExpose({ focus: () => input.value?.focus() })
const sizeKb = (b: number) => (b > 1024 * 1024 ? `${(b / 1024 / 1024).toFixed(1)} MB` : `${Math.round(b / 1024)} KB`)
</script>

<template>
  <div class="border-t border-default bg-default px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-2 sm:px-4">
    <!-- Javob / tahrirlash paneli -->
    <div v-if="editing || replyTo" class="mb-2 flex items-center gap-3 rounded-xl border-l-4 border-brand-400 bg-brand-50 px-3 py-2 dark:bg-brand-950">
      <UIcon :name="editing ? 'i-lucide-pencil' : 'i-lucide-reply'" class="size-5 shrink-0 text-brand-600 dark:text-brand-300" aria-hidden="true" />
      <div class="min-w-0 flex-1 text-sm">
        <p class="font-bold text-brand-700 dark:text-brand-200">{{ editing ? t('chat.editing') : t('chat.replyingTo', { name: replyAuthor }) }}</p>
        <p class="truncate text-muted">{{ previewText(replyTo?.type ?? editing?.type, (editing ?? replyTo)?.body ?? (replyTo?.type === 'image' ? '📷 Rasm' : '🎤 Ovozli xabar')) }}</p>
      </div>
      <UButton color="neutral" variant="ghost" size="sm" icon="i-lucide-x" :aria-label="t('chat.cancel')" @click="editing ? (text = '', emit('cancelEdit')) : emit('cancelReply')" />
    </div>

    <!-- Rasm oldindan koʻrish -->
    <div v-if="image" class="mb-2 flex items-center gap-3 rounded-xl border border-default bg-elevated/50 p-2">
      <img :src="image.url" alt="" class="size-14 rounded-lg object-cover">
      <div class="min-w-0 flex-1 text-sm">
        <p class="truncate font-medium text-highlighted">{{ image.file.name }}</p>
        <p class="text-muted">{{ sizeKb(image.file.size) }}</p>
      </div>
      <UButton color="neutral" variant="ghost" size="sm" icon="i-lucide-x" :aria-label="t('chat.removePhoto')" @click="clearImage" />
    </div>

    <!-- Ovoz yozish -->
    <div v-if="voice.recording.value" class="flex items-center gap-2" role="group" :aria-label="t('chat.recording')">
      <UButton color="neutral" variant="ghost" size="lg" icon="i-lucide-trash-2" :aria-label="t('chat.cancelRecording')" @click="voice.cancel()" />
      <p class="flex flex-1 items-center gap-2.5 font-medium tabular-nums text-highlighted" role="timer">
        <span class="size-3 animate-pulse rounded-full bg-pomegranate-500" aria-hidden="true" />
        {{ fmt(voice.seconds.value) }}
        <span class="text-sm font-normal text-muted">/ {{ fmt(MAX_VOICE_SECONDS) }}</span>
      </p>
      <UButton size="lg" icon="i-lucide-send" class="rounded-full" :aria-label="t('chat.sendVoice')" @click="voice.stop()" />
    </div>

    <div v-else class="flex items-end gap-1.5">
      <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" @change="pick">
      <UButton
        v-if="!editing"
        color="neutral"
        variant="ghost"
        size="lg"
        icon="i-lucide-paperclip"
        class="mb-0.5 rounded-full"
        :aria-label="t('chat.attachPhoto')"
        @click="fileInput?.click()"
      />
      <textarea
        ref="input"
        v-model="text"
        rows="1"
        maxlength="4000"
        class="max-h-36 min-h-11 flex-1 resize-none rounded-2xl border border-default bg-white px-4 py-2.5 text-base leading-snug text-highlighted placeholder:text-dimmed focus:border-brand-500 focus:outline-2 focus:outline-brand-500/30 dark:bg-sand-800"
        :placeholder="image ? t('chat.caption') : t('chat.placeholder')"
        :aria-label="t('chat.placeholder')"
        enterkeyhint="send"
        @keydown="onKey"
        @input="onInput"
      />
      <UButton
        v-if="canSend || editing"
        size="lg"
        :icon="editing ? 'i-lucide-check' : 'i-lucide-send-horizontal'"
        class="mb-0.5 rounded-full"
        :loading="saving"
        :disabled="editing ? !text.trim() : false"
        :aria-label="editing ? t('chat.saveEdit') : t('chat.send')"
        @click="submit"
      />
      <UButton
        v-else
        size="lg"
        color="neutral"
        variant="soft"
        icon="i-lucide-mic"
        class="mb-0.5 rounded-full"
        :aria-label="t('chat.recordVoice')"
        @click="voice.start()"
      />
    </div>
  </div>
</template>
