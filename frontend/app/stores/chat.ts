import { defineStore } from 'pinia'
import type Echo from 'laravel-echo'

interface Thread {
  items: ChatMessage[]
  hasMore: boolean
  loaded: boolean
  loading: boolean
  error: boolean
}

type SendOpts = { type: 'text' | 'image' | 'voice', body?: string, file?: Blob, fileName?: string, duration?: number, replyTo?: ChatMessage | null }

/** Chat holati: suhbatlar, oʻqilmaganlar, onlayn foydalanuvchilar, xabarlar keshi va realtime ulanish. */
export const useChatStore = defineStore('chat', () => {
  const auth = useAuthStore()
  const api = auth.api

  const conversations = reactive<Record<number, Conversation>>({})
  const unreadTotal = ref(0)
  const online = shallowRef<Set<number>>(new Set())
  const threads = reactive<Record<number, Thread>>({})
  /** Suhbatdoshning oxirgi oʻqigan xabar id'si (roʻyxatdagi ✓✓ uchun). */
  const otherRead = reactive<Record<number, number>>({})
  const list = reactive({ loaded: false, loading: false, error: false, hasMore: false, page: 1 })
  const missing = reactive<Record<number, true>>({})

  const meId = () => auth.user?.id ?? 0
  const lastReadSent: Record<number, number> = {}
  const payloads = new Map<string, SendOpts>()
  let seq = 0

  const sorted = computed(() =>
    Object.values(conversations)
      .filter(c => c.last_message)
      .sort((a, b) => (b.last_message!.created_at ?? '').localeCompare(a.last_message!.created_at ?? '') || b.id - a.id),
  )

  // --- Suhbatlar ---
  function upsertConversation(c: Conversation) {
    conversations[c.id] = { ...conversations[c.id], ...c }
  }

  async function fetchConversations(more = false) {
    if (list.loading) return
    list.loading = true
    list.error = false
    try {
      const page = more ? list.page + 1 : 1
      const res = await api<{ data: Conversation[], links?: { next: string | null }, meta?: { current_page?: number, has_more_pages?: boolean } }>('/conversations', { query: { page } })
      res.data.forEach(upsertConversation)
      list.page = page
      list.hasMore = !!res.links?.next
      list.loaded = true
    }
    catch {
      list.error = true
    }
    finally {
      list.loading = false
    }
  }

  async function fetchConversation(id: number): Promise<Conversation | null> {
    try {
      const c = (await api<{ data: Conversation }>(`/conversations/${id}`)).data
      upsertConversation(c)
      delete missing[id]
      return c
    }
    catch (e) {
      const status = parseApiError(e).status
      if (status === 403 || status === 404) missing[id] = true
      return null
    }
  }

  async function fetchUnread() {
    try {
      unreadTotal.value = (await api<{ total: number }>('/chat/unread')).total
    }
    catch { /* belgi muhim emas */ }
  }

  function setUnread(id: number, n: number) {
    const c = conversations[id]
    if (!c) return
    unreadTotal.value = Math.max(0, unreadTotal.value + (n - c.unread_count))
    c.unread_count = n
  }

  function applyUpdated(p: { conversation_id: number, unread_count: number, last_message: ChatLastMessage | null }) {
    const c = conversations[p.conversation_id]
    if (!c) {
      fetchConversation(p.conversation_id).then(fetchUnread)
      return
    }
    setUnread(p.conversation_id, p.unread_count)
    c.last_message = p.last_message
  }

  // --- Xabarlar ---
  function thread(id: number): Thread {
    return (threads[id] ??= { items: [], hasMore: false, loaded: false, loading: false, error: false })
  }

  function sortItems(items: ChatMessage[]) {
    items.sort((a, b) => (a.pending ? 1 : 0) - (b.pending ? 1 : 0) || (a.pending ? a.created_at.localeCompare(b.created_at) : a.id - b.id))
  }

  function upsertMessage(m: ChatMessage) {
    const th = thread(m.conversation_id)
    const i = th.items.findIndex(x => x.id === m.id)
    if (i >= 0) {
      th.items[i] = { ...th.items[i], ...m, pending: undefined }
    }
    else {
      th.items.push(m)
      sortItems(th.items)
    }
    if (m.deleted) {
      for (const x of th.items) if (x.reply_to?.id === m.id) x.reply_to = { ...x.reply_to, preview: null }
    }
  }

  async function loadMessages(id: number, older = false) {
    const th = thread(id)
    if (th.loading) return
    th.loading = true
    th.error = false
    try {
      const firstReal = th.items.find(m => !m.pending)
      const res = await api<{ data: ChatMessage[], meta: { has_more: boolean } }>(`/conversations/${id}/messages`, {
        query: older && firstReal ? { before_id: firstReal.id, limit: 30 } : { limit: 30 },
      })
      if (older) {
        const known = new Set(th.items.map(m => m.id))
        th.items = [...res.data.filter(m => !known.has(m.id)), ...th.items]
        th.hasMore = res.meta.has_more
      }
      else {
        const pending = th.items.filter(m => m.pending)
        th.items = [...res.data, ...pending]
        th.hasMore = res.meta.has_more
        th.loaded = true
      }
      const lastMineRead = [...th.items].reverse().find(m => m.sender_id === meId() && m.read && !m.pending)
      if (lastMineRead) otherRead[id] = Math.max(otherRead[id] ?? 0, lastMineRead.id)
    }
    catch {
      th.error = true
    }
    finally {
      th.loading = false
    }
  }

  /** Ulanish tiklangandan keyin: oʻtkazib yuborilgan xabarlarni olib kelish. */
  async function catchUp(id: number) {
    try {
      const res = await api<{ data: ChatMessage[] }>(`/conversations/${id}/messages`, { query: { limit: 30 } })
      res.data.forEach(upsertMessage)
    }
    catch { /* keyingi ulanishda */ }
  }

  function applyRead(id: number, userId: number, lastReadId: number) {
    if (userId === meId()) return
    otherRead[id] = Math.max(otherRead[id] ?? 0, lastReadId)
    for (const m of thread(id).items) if (m.sender_id === meId() && !m.pending && m.id <= lastReadId) m.read = true
  }

  async function markRead(id: number, messageId: number) {
    if (messageId <= 0 || messageId <= (lastReadSent[id] ?? 0)) return
    lastReadSent[id] = messageId
    try {
      const res = await api<{ data: { unread_count: number } }>(`/conversations/${id}/read`, { method: 'POST', body: { message_id: messageId } })
      setUnread(id, res.data.unread_count)
    }
    catch {
      delete lastReadSent[id]
    }
  }

  function preview(o: SendOpts) {
    return o.type === 'image' ? '📷 Rasm' : o.type === 'voice' ? '🎤 Ovozli xabar' : (o.body ?? '')
  }

  async function transmit(convId: number, clientId: string) {
    const th = thread(convId)
    const o = payloads.get(clientId)!
    const idx = () => th.items.findIndex(m => m.clientId === clientId && m.pending)
    let body: object | FormData
    if (o.type === 'text') {
      body = { type: 'text', body: o.body, reply_to_id: o.replyTo?.id }
    }
    else {
      const fd = new FormData()
      fd.append('type', o.type)
      fd.append('file', o.file!, o.fileName)
      if (o.body) fd.append('body', o.body)
      if (o.duration) fd.append('duration', String(o.duration))
      if (o.replyTo) fd.append('reply_to_id', String(o.replyTo.id))
      body = fd
    }
    try {
      const res = await api<{ data: ChatMessage }>(`/conversations/${convId}/messages`, { method: 'POST', body })
      const real = res.data
      const at = idx()
      const local = at >= 0 ? th.items[at] : undefined
      const dup = th.items.findIndex(m => m.id === real.id)
      if (dup >= 0) {
        th.items[dup] = { ...th.items[dup]!, ...real }
        if (at >= 0) th.items.splice(at, 1)
      }
      else if (at >= 0) {
        th.items[at] = { ...real, clientId }
        sortItems(th.items)
      }
      if (local?.attachment?.url.startsWith('blob:')) URL.revokeObjectURL(local.attachment.url)
      payloads.delete(clientId)
      const c = conversations[convId]
      if (c) c.last_message = { id: real.id, type: real.type, preview: real.deleted ? '' : (real.type === 'text' ? (real.body ?? '') : preview(o)), sender_is_me: true, created_at: real.created_at }
    }
    catch (e) {
      const at = idx()
      if (at >= 0) th.items[at]!.pending = 'failed'
      const p = parseApiError(e)
      if (p.status === 403) fetchConversation(convId)
      return p
    }
    return null
  }

  /** Optimistik yuborish: darhol koʻrinadi, 201 kelganda haqiqiy xabarga almashadi. */
  async function send(convId: number, o: SendOpts) {
    const clientId = `c${Date.now()}-${++seq}`
    payloads.set(clientId, o)
    const th = thread(convId)
    const now = new Date().toISOString()
    th.items.push({
      id: -seq,
      conversation_id: convId,
      sender_id: meId(),
      type: o.type,
      body: o.body || null,
      attachment: o.file ? { url: URL.createObjectURL(o.file), mime: o.file.type, duration: o.duration } : null,
      reply_to: o.replyTo ? { id: o.replyTo.id, preview: o.replyTo.body ?? (o.replyTo.type === 'image' ? '📷 Rasm' : '🎤 Ovozli xabar'), sender_id: o.replyTo.sender_id } : null,
      edited_at: null,
      deleted: false,
      created_at: now,
      read: false,
      meta: {},
      pending: 'sending',
      clientId,
    })
    const c = conversations[convId]
    if (c) c.last_message = { id: -seq, type: o.type, preview: preview(o), sender_is_me: true, created_at: now }
    return transmit(convId, clientId)
  }

  async function retry(m: ChatMessage) {
    if (!m.clientId || !payloads.has(m.clientId)) return
    const th = thread(m.conversation_id)
    const it = th.items.find(x => x.clientId === m.clientId)
    if (it) it.pending = 'sending'
    return transmit(m.conversation_id, m.clientId)
  }

  function discard(m: ChatMessage) {
    const th = thread(m.conversation_id)
    th.items = th.items.filter(x => x.clientId !== m.clientId)
    if (m.clientId) payloads.delete(m.clientId)
    if (m.attachment?.url.startsWith('blob:')) URL.revokeObjectURL(m.attachment.url)
  }

  async function edit(m: ChatMessage, body: string) {
    const res = await api<{ data: ChatMessage }>(`/messages/${m.id}`, { method: 'PATCH', body: { body } })
    upsertMessage(res.data)
    const c = conversations[m.conversation_id]
    if (c?.last_message?.id === m.id) c.last_message = { ...c.last_message, preview: res.data.body ?? '' }
  }

  async function remove(m: ChatMessage) {
    await api(`/messages/${m.id}`, { method: 'DELETE' })
    upsertMessage({ ...m, deleted: true, body: null, attachment: null })
    const c = conversations[m.conversation_id]
    if (c?.last_message?.id === m.id) c.last_message = { ...c.last_message, preview: 'Xabar o\'chirildi' }
  }

  async function setMuted(id: number, muted: boolean) {
    const res = await api<{ data: Conversation }>(`/conversations/${id}/mute`, { method: 'POST', body: { muted } })
    upsertConversation(res.data)
  }

  async function setBlocked(id: number, blocked: boolean) {
    const res = await api<{ data: Conversation }>(`/conversations/${id}/block`, { method: blocked ? 'POST' : 'DELETE' })
    upsertConversation(res.data)
  }

  // --- Realtime ---
  let echo: Echo<'reverb'> | null = null
  let wasDown = false

  function resync() {
    fetchUnread()
    if (list.loaded) fetchConversations()
    for (const id of Object.keys(threads)) if (threads[Number(id)]!.loaded) catchUp(Number(id))
  }

  function connect(instance: Echo<'reverb'>) {
    disconnect()
    echo = instance
    const id = meId()
    echo.private(`user.${id}`).listen('.conversation.updated', applyUpdated).subscribed(fetchUnread)
    echo.join('online')
      .here((users: { id: number }[]) => { online.value = new Set(users.map(u => u.id)) })
      .joining((u: { id: number }) => { online.value = new Set(online.value).add(u.id) })
      .leaving((u: { id: number }) => { const s = new Set(online.value); s.delete(u.id); online.value = s })
    const conn = (echo.connector as unknown as { pusher: { connection: { bind: (e: string, cb: (s: { current: string }) => void) => void } } }).pusher.connection
    conn.bind('state_change', ({ current }) => {
      if (current === 'connected' && wasDown) { wasDown = false; resync() }
      else if (current === 'unavailable' || current === 'disconnected' || current === 'failed') wasDown = true
    })
    fetchUnread()
  }

  function disconnect() {
    if (echo) {
      try { echo.leave(`user.${meId()}`); echo.leave('online') } catch { /* ulanish yopilgan */ }
    }
    echo = null
    online.value = new Set()
  }

  /** Chiqishda barcha holatni tozalash. */
  function reset() {
    disconnect()
    for (const k of Object.keys(conversations)) delete conversations[Number(k)]
    for (const k of Object.keys(threads)) delete threads[Number(k)]
    for (const k of Object.keys(otherRead)) delete otherRead[Number(k)]
    for (const k of Object.keys(missing)) delete missing[Number(k)]
    unreadTotal.value = 0
    Object.assign(list, { loaded: false, loading: false, error: false, hasMore: false, page: 1 })
    payloads.clear()
    for (const k of Object.keys(lastReadSent)) delete lastReadSent[Number(k)]
  }

  const isOnline = (userId: number | undefined) => !!userId && online.value.has(userId)

  return {
    conversations, sorted, unreadTotal, online, threads, otherRead, list, missing,
    isOnline, thread, upsertConversation, fetchConversations, fetchConversation, fetchUnread, applyUpdated,
    upsertMessage, loadMessages, applyRead, markRead, send, retry, discard, edit, remove, setMuted, setBlocked,
    connect, disconnect, reset,
  }
})
