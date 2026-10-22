/** URL query = filtr holati. Faqat ruxsat etilgan kalitlar olinadi; oʻzgarganda 1-sahifaga qaytadi. */
export function useSearchQuery(allowed: readonly string[]) {
  const route = useRoute()
  const router = useRouter()

  const filters = computed(() => {
    const out: Record<string, string> = {}
    for (const key of [...allowed, 'page']) {
      const v = route.query[key]
      const s = Array.isArray(v) ? v[0] : v
      if (s) out[key] = s
    }
    return out
  })

  const page = computed(() => Math.max(1, Number(filters.value.page) || 1))
  const activeCount = computed(() => Object.keys(filters.value).filter(k => !['page', 'sort', 'price_unit', 'salary_unit'].includes(k)).length)

  /** patch: null/''/undefined = filtrni olib tashlash */
  function set(patch: Record<string, string | number | boolean | null | undefined>, keepPage = false) {
    const next: Record<string, string> = { ...filters.value }
    for (const [k, v] of Object.entries(patch)) {
      if (v === null || v === undefined || v === '' || v === false) delete next[k]
      else next[k] = v === true ? '1' : String(v)
    }
    if (!keepPage && !('page' in patch)) delete next.page
    if (next.page === '1') delete next.page
    return router.push({ query: next })
  }

  function reset() {
    const next: Record<string, string> = {}
    if (filters.value.sort) next.sort = filters.value.sort
    return router.push({ query: next })
  }

  return { filters, page, activeCount, set, reset }
}
