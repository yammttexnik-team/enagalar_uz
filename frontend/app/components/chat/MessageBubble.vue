<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

const props = defineProps<{
  message: ChatMessage
  mine: boolean
  items: DropdownMenuItem[]
  replyDeleted?: boolean
  replyAuthor?: string
  highlighted?: boolean
}>()
const emit = defineEmits<{ image: [m: ChatMessage], jump: [id: number], sheet: [m: ChatMessage], retry: [m: ChatMessage], discard: [m: ChatMessage] }>()
const { t } = useI18n()
const { time } = useChatFormat()
const previewText = useChatPreview()

const m = computed(() => props.message)
const hasMenu = computed(() => !m.value.pending && props.items.flat().length > 0)
const replyText = computed(() => (props.replyDeleted || m.value.reply_to?.preview == null ? t('chat.deleted') : previewText(undefined, m.value.reply_to.preview)))

// Mobil: uzoq bosish → amallar varaqasi
let press: ReturnType<typeof setTimeout> | undefined
function down() {
  if (!hasMenu.value) return
  clearTimeout(press)
  press = setTimeout(() => emit('sheet', m.value), 450)
}
const cancel = () => clearTimeout(press)
function ctx(e: Event) {
  if (window.matchMedia('(pointer: coarse)').matches && hasMenu.value) { e.preventDefault(); emit('sheet', m.value) }
}
</script>

<template>
  <li
    :id="`m-${m.id}`"
    class="group/b flex items-end gap-1"
    :class="mine ? 'flex-row-reverse' : 'flex-row'"
    :data-mid="m.id > 0 ? m.id : undefined"
    :data-incoming="!mine ? '' : undefined"
  >
    <div
      class="relative max-w-[85%] select-text sm:max-w-[72%]"
      @touchstart.passive="down"
      @touchend="cancel"
      @touchmove.passive="cancel"
      @touchcancel="cancel"
      @contextmenu="ctx"
    >
      <div
        class="rounded-2xl px-3 py-2 text-[15px] leading-snug transition-shadow [overflow-wrap:anywhere]"
        :class="[
          mine ? 'rounded-br-md bg-brand-500 text-white dark:bg-brand-600' : 'rounded-bl-md border border-default bg-white text-default shadow-xs dark:bg-sand-800',
          highlighted ? 'ring-2 ring-apricot-400' : '',
          m.pending === 'sending' ? 'opacity-70' : '',
        ]"
      >
        <template v-if="m.deleted">
          <p class="flex items-center gap-1.5 italic" :class="mine ? 'text-white/85' : 'text-muted'">
            <UIcon name="i-lucide-ban" class="size-4" aria-hidden="true" />{{ t('chat.deleted') }}
          </p>
        </template>
        <template v-else>
          <button
            v-if="m.reply_to"
            type="button"
            class="mb-1.5 block w-full rounded-lg border-l-4 px-2.5 py-1 text-left text-sm focus-visible:outline-2"
            :class="mine ? 'border-white/80 bg-white/15 hover:bg-white/25' : 'border-brand-400 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950 dark:hover:bg-brand-900'"
            :aria-label="t('chat.jumpToReply')"
            @click="emit('jump', m.reply_to!.id)"
          >
            <span class="block text-xs font-bold" :class="mine ? 'text-white' : 'text-brand-700 dark:text-brand-200'">{{ replyAuthor }}</span>
            <span class="line-clamp-2" :class="[mine ? 'text-white/90' : 'text-muted', replyDeleted ? 'italic' : '']">{{ replyText }}</span>
          </button>

          <button
            v-if="m.type === 'image' && m.attachment"
            type="button"
            class="block overflow-hidden rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2"
            :aria-label="t('chat.openPhoto')"
            @click="emit('image', m)"
          >
            <img :src="m.attachment.url" :alt="m.body || t('chat.photo')" class="h-44 w-56 bg-sand-200 object-cover sm:h-52 sm:w-64 dark:bg-sand-700" loading="lazy">
          </button>
          <ChatVoicePlayer v-else-if="m.type === 'voice' && m.attachment" :src="m.attachment.url" :duration="m.attachment.duration" :mine="mine" />
          <p v-if="m.body" class="whitespace-pre-wrap" :class="m.type !== 'text' ? 'mt-1.5' : ''">{{ m.body }}</p>
        </template>

        <p class="mt-0.5 flex items-center justify-end gap-1 text-[11px] leading-none" :class="mine ? 'text-white/80' : 'text-muted'">
          <span v-if="m.edited_at && !m.deleted">{{ t('chat.edited') }}</span>
          <time :datetime="m.created_at">{{ time(m.created_at) }}</time>
          <template v-if="mine">
            <UIcon v-if="m.pending === 'sending'" name="i-lucide-clock" class="size-3.5" aria-hidden="true" />
            <UIcon v-else-if="m.pending === 'failed'" name="i-lucide-circle-alert" class="size-3.5 text-apricot-200" aria-hidden="true" />
            <UIcon v-else :name="m.read ? 'i-lucide-check-check' : 'i-lucide-check'" class="size-3.5" aria-hidden="true" />
            <span class="sr-only">{{ m.pending === 'sending' ? t('chat.sending') : m.pending === 'failed' ? t('chat.failed') : m.read ? t('chat.read') : t('chat.sent') }}</span>
          </template>
        </p>
      </div>

      <div v-if="m.pending === 'failed'" class="mt-1 flex items-center justify-end gap-2 text-xs">
        <span class="font-semibold text-pomegranate-600 dark:text-pomegranate-300">{{ t('chat.notSent') }}</span>
        <UButton size="xs" color="neutral" variant="soft" icon="i-lucide-rotate-cw" :label="t('chat.retry')" @click="emit('retry', m)" />
        <UButton size="xs" color="neutral" variant="ghost" icon="i-lucide-trash-2" :aria-label="t('chat.discard')" @click="emit('discard', m)" />
      </div>
    </div>

    <UDropdownMenu v-if="hasMenu" :items="items" :content="{ align: mine ? 'end' : 'start' }">
      <UButton
        color="neutral"
        variant="ghost"
        size="xs"
        icon="i-lucide-ellipsis-vertical"
        class="shrink-0 opacity-0 transition-opacity group-hover/b:opacity-100 focus-visible:opacity-100 pointer-coarse:hidden data-[state=open]:opacity-100"
        :aria-label="t('chat.messageActions')"
      />
    </UDropdownMenu>
  </li>
</template>
