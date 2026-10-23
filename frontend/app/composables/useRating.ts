/** Bog'lanish ("Birga ishladingizmi?") — ko'ruvchi nuqtai nazaridan (GET /my/connections). */
export interface ConnectionItem {
  id: number
  status: 'question' | 'waiting' | 'not_worked' | 'can_review' | 'reviewed'
  my_role: 'parent' | 'nanny'
  other: { id: number, name: string, role: 'parent' | 'nanny', nanny_profile_id: number | null } | null
  conversation_id: number
  my_answer: boolean | null
  connected_at: string | null
  my_review: { rating: number, comment: string | null, hidden: boolean } | null
}

/** Ochiq sharh (GET /nannies/{id}/reviews). */
export interface ReviewItem {
  id: number
  rating: number
  comment: string | null
  author: string | null
  created_at: string | null
}

export function useConnections() {
  const api = useApi()

  const list = () => api<{ data: ConnectionItem[], meta: { pending_count: number } }>('/my/connections')

  const answer = (id: number, worked: boolean) =>
    api<{ data: ConnectionItem }>(`/connections/${id}/answer`, { method: 'POST', body: { worked } }).then(r => r.data)

  const review = (id: number, rating: number, comment: string) =>
    api<{ data: ReviewItem }>(`/connections/${id}/review`, { method: 'POST', body: { rating, comment: comment.trim() || null } }).then(r => r.data)

  return { list, answer, review }
}
