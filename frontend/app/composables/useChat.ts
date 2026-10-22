export type ChatMessageType = 'text' | 'image' | 'voice' | 'file'

export interface ChatAttachment { url: string, mime?: string | null, size?: number | null, duration?: number }

export interface ChatMessage {
  id: number
  conversation_id: number
  sender_id: number | null
  type: ChatMessageType
  body: string | null
  attachment: ChatAttachment | null
  reply_to: { id: number, preview: string | null, type?: ChatMessageType, sender_id: number | null } | null
  edited_at: string | null
  deleted: boolean
  created_at: string
  read: boolean
  meta: { contact_warning?: boolean }
  /** Faqat mijozda: yuborilmoqda / xato. */
  pending?: 'sending' | 'failed'
  /** Optimistik xabarning vaqtinchalik kaliti; haqiqiy xabar kelganda ham saqlanadi (DOM qayta yaratilmasligi uchun). */
  clientId?: string
}

export interface ChatOther {
  id: number
  name: string
  initials: string
  role: 'nanny' | 'parent'
  nanny_profile_id: number | null
  verified: boolean
  last_seen_at: string | null
}

export interface ChatLastMessage { id: number, type: ChatMessageType, preview: string, sender_is_me: boolean, created_at: string | null, deleted?: boolean }

export interface Conversation {
  id: number
  other: ChatOther | null
  vacancy: { id: number, title: string } | null
  last_message: ChatLastMessage | null
  unread_count: number
  muted: boolean
  blocked_by_me: boolean
  blocked_me: boolean
  created_at: string | null
}

export const REPORT_REASONS = ['spam', 'fraud', 'abuse', 'inappropriate', 'other'] as const
export const MAX_IMAGE_BYTES = 10 * 1024 * 1024
export const MAX_VOICE_SECONDS = 180
export const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp']

export const messageKey = (m: ChatMessage) => m.clientId ?? String(m.id)

/** Serverdan kelgan (faqat oʻzbekcha) qisqa matnlarni joriy tilga moslaydi. */
export function useChatPreview() {
  const { t } = useI18n()
  return (type: string | undefined, preview: string | null | undefined, deleted?: boolean) => {
    if (deleted) return t('chat.deleted')
    if (type === 'image' || preview === '📷 Rasm') return t('chat.photo')
    if (type === 'voice' || preview === '🎤 Ovozli xabar') return t('chat.voiceMessage')
    if (type === 'file' || preview === '📎 Fayl') return t('chat.file')
    if (preview === 'Xabar o\'chirildi') return t('chat.deleted')
    return preview ?? ''
  }
}

const pad = (n: number) => String(n).padStart(2, '0')

/** Soat, kun nomi va sana — brauzerning mahalliy vaqt zonasida. */
export function useChatFormat() {
  const { t, locale } = useI18n()
  const time = (iso: string | null | undefined) => {
    if (!iso) return ''
    const d = new Date(iso)
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`
  }
  const dayKey = (iso: string) => {
    const d = new Date(iso)
    return `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`
  }
  function fullDate(iso: string) {
    const d = new Date(iso)
    const sameYear = d.getFullYear() === new Date().getFullYear()
    return new Intl.DateTimeFormat(locale.value === 'ru' ? 'ru-RU' : 'uz-Latn', { day: 'numeric', month: 'long', ...(sameYear ? {} : { year: 'numeric' }) }).format(d)
  }
  function dayLabel(iso: string) {
    const now = new Date()
    const yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1)
    const key = dayKey(iso)
    if (key === dayKey(now.toISOString())) return t('chat.today')
    if (key === dayKey(yesterday.toISOString())) return t('chat.yesterday')
    return fullDate(iso)
  }
  /** Roʻyxat uchun: bugun — soat, kecha — "Kecha", boshqa — "12 oktabr". */
  function listTime(iso: string | null | undefined) {
    if (!iso) return ''
    const label = dayLabel(iso)
    return label === t('chat.today') ? time(iso) : label === t('chat.yesterday') ? label : fullDate(iso)
  }
  const duration = (sec: number) => `${Math.floor(sec / 60)}:${pad(Math.floor(sec % 60))}`
  return { time, dayKey, dayLabel, listTime, fullDate, duration }
}

/** Suhbat boshlash: ota-onadan enagaga, enagadan e'longa yoki javob (application) orqali. */
export function useStartChat() {
  const { t } = useI18n()
  const api = useApi()
  const toast = useToast()
  const localePath = useLocalePath()
  const loading = ref(false)

  function friendly(e: unknown) {
    const p = parseApiError(e)
    if (p.status === undefined) return t('dashboard.common.offline')
    if (p.status === 403) return t('chat.errors.forbidden')
    if (p.status === 422) {
      if (p.fields.nanny_profile_id) return t('chat.errors.nannyInactive')
      if (p.fields.vacancy_id) return t('chat.errors.vacancyInactive')
      if (p.fields.user) return t('chat.errors.self')
    }
    if (p.status === 429) return t('chat.errors.tooMany')
    return t('dashboard.common.error')
  }

  async function start(body: { nanny_profile_id?: number, vacancy_id?: number, application_id?: number }) {
    if (loading.value) return
    loading.value = true
    try {
      const res = await api<{ data: Conversation }>('/conversations', { method: 'POST', body })
      await navigateTo(localePath(`/kabinet/xabarlar/${res.data.id}`))
    }
    catch (e) {
      toast.add({ title: friendly(e), color: 'error', icon: 'i-lucide-circle-alert' })
    }
    finally {
      loading.value = false
    }
  }
  return { start, loading }
}
