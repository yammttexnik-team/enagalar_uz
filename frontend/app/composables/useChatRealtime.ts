/**
 * Bitta suhbat kanaliga (private conversation.{id}) obuna: yangi/tahrirlangan/oʻchirilgan xabar,
 * "oʻqildi" va "yozmoqda" (whisper). Suhbat oʻzgarsa yoki sahifadan chiqilsa kanaldan chiqadi.
 */
export function useChatRealtime(conversationId: Ref<number | null>) {
  const chat = useChatStore()
  const auth = useAuthStore()
  const nuxt = useNuxtApp() as unknown as { $echo?: { instance: Ref<import('laravel-echo').default<'reverb'> | null> } }
  const typing = ref(false)
  const connected = ref(false)
  let channel: { whisper: (e: string, d: object) => unknown } | null = null
  let hideTimer: ReturnType<typeof setTimeout> | undefined
  let lastWhisper = 0

  const stop = watch(
    [() => nuxt.$echo?.instance.value, conversationId],
    ([echo, id], _old, onCleanup) => {
      connected.value = !!echo
      if (!echo || !id) return
      const name = `conversation.${id}`
      const ch = echo.private(name)
      channel = ch as unknown as typeof channel
      ch.listen('.message.sent', (m: ChatMessage) => {
        chat.upsertMessage(m)
        if (m.sender_id !== auth.user?.id) { typing.value = false; clearTimeout(hideTimer) }
      })
        .listen('.message.updated', (m: ChatMessage) => chat.upsertMessage(m))
        .listen('.message.deleted', (m: ChatMessage) => chat.upsertMessage(m))
        .listen('.conversation.read', (e: { user_id: number, last_read_message_id: number }) => chat.applyRead(id, e.user_id, e.last_read_message_id))
        .listenForWhisper('typing', (e: { user_id?: number }) => {
          if (e.user_id === auth.user?.id) return
          typing.value = true
          clearTimeout(hideTimer)
          hideTimer = setTimeout(() => { typing.value = false }, 4000)
        })
      onCleanup(() => {
        try { echo.leave(name) } catch { /* ulanish yopilgan */ }
        channel = null
        typing.value = false
        clearTimeout(hideTimer)
      })
    },
    { immediate: true },
  )
  onScopeDispose(stop)

  /** Har 2 soniyada bir martadan koʻp yuborilmaydi. */
  function sendTyping() {
    const now = Date.now()
    if (!channel || now - lastWhisper < 2000) return
    lastWhisper = now
    try { channel.whisper('typing', { user_id: auth.user?.id }) } catch { /* ulanmagan */ }
  }

  return { typing, connected, sendTyping }
}
