import type { Badge, LocalizedName } from '~/composables/useApi'
import type { NannyItem } from '~/components/nanny/Card.vue'
import type { VacancyItem } from '~/components/vacancy/Card.vue'

export interface Paginated<T> { data: T[], meta: { total: number, per_page: number, current_page: number, last_page: number } }

export type VacancyStatus = 'draft' | 'pending' | 'active' | 'rejected' | 'closed' | 'found' | 'expired'
export type NannyStatus = 'draft' | 'pending' | 'active' | 'rejected' | 'hidden'
export type ApplicationStatus = 'sent' | 'viewed' | 'shortlisted' | 'rejected'

export interface OwnVacancy extends VacancyItem {
  status: VacancyStatus
  region_id: number | null
  district_id: number | null
  views_count: number
  applications_count?: number
}

export interface OwnNanny extends NannyItem {
  status: NannyStatus
  region_id: number | null
  district_id: number | null
  birth_year: number | null
  bio: string | null
  age_groups: string[]
  free_trial_day: boolean
  skills: { id: number, slug: string, name: LocalizedName }[]
  views_count: number
}

export interface Application {
  id: number
  status: ApplicationStatus
  message: string | null
  created_at: string
  vacancy?: VacancyItem
  nanny?: NannyItem
}

export interface MatchItem<T> {
  score: number
  reason: { uz: string | null, ru: string | null }
  source: 'ai' | 'rules'
  computed_at: string | null
  nanny?: T
  vacancy?: T
}

export interface VerificationState {
  identity: { status: 'none' | 'pending' | 'approved' | 'rejected', rejection_reason: string | null, submitted_at: string | null, verified: boolean }
  documents: { id: number, badge: { slug: string, name: LocalizedName | string }, status: string, rejection_reason: string | null, issued_at: string | null, submitted_at: string | null }[]
  available_badges: { slug: string, name: LocalizedName, description: { uz: string | null, ru: string | null }, validity_days: number | null, held: boolean, expires_at: string | null, has_pending: boolean }[]
}

export type { Badge }

/** Faqat shu saytning ichki yoʻli (open-redirect himoyasi): "/" bilan boshlanadi, "//" yoki "\" yoʻq. */
export function safeRedirect(v: unknown): string | null {
  const s = Array.isArray(v) ? v[0] : v
  if (typeof s !== 'string' || !s.startsWith('/') || s.startsWith('//') || s.includes('\\') || /[\u0000-\u001f]/.test(s)) return null
  return s
}

/** Kirgan boʻlsa — toʻgʻridan-toʻgʻri yoʻl, boʻlmasa /kirish?redirect=yoʻl. */
export function useAuthLink() {
  const auth = useAuthStore()
  const localePath = useLocalePath()
  return (path: string) => {
    const target = localePath(path)
    return auth.isLoggedIn ? target : localePath({ path: '/kirish', query: { redirect: target } })
  }
}

export interface ParsedError { status: number | undefined, message: string, fields: Record<string, string> }

/** Laravel xatosini (422 maydonlar bilan) oddiy shaklga keltiradi. */
export function parseApiError(e: unknown): ParsedError {
  const err = e as { statusCode?: number, status?: number, data?: { message?: string, errors?: Record<string, string[]> } }
  const fields: Record<string, string> = {}
  for (const [k, v] of Object.entries(err.data?.errors ?? {})) fields[k] = v[0] ?? ''
  return { status: err.statusCode ?? err.status, message: err.data?.message ?? '', fields }
}

export function useApiError() {
  const { t } = useI18n()
  const toast = useToast()
  function toastError(e: unknown, fallback?: string) {
    const p = parseApiError(e)
    const text = p.status === undefined ? t('dashboard.common.offline') : (p.status === 422 || p.status === 409 || p.status === 429) && p.message ? p.message : (fallback ?? t('dashboard.common.error'))
    toast.add({ title: text, color: 'error', icon: 'i-lucide-circle-alert' })
    return p
  }
  function ok(title: string) {
    toast.add({ title, color: 'success', icon: 'i-lucide-circle-check' })
  }
  return { toastError, ok }
}

/** Ota-onaning bitta e'loni (GET /my/vacancies/{id}); topilmasa yoki boshqaniki bo'lsa null. */
export async function fetchMyVacancy(api: ReturnType<typeof useApi>, id: number | string): Promise<OwnVacancy | null> {
  try {
    const res = await api<{ data: OwnVacancy }>(`/my/vacancies/${id}`)
    return res.data
  } catch (e: any) {
    const status = e?.statusCode ?? e?.response?.status
    if (status === 404 || status === 403) return null
    throw e
  }
}
