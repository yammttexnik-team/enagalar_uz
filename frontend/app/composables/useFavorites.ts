type FavType = 'nanny' | 'vacancy'

/** Sevimlilar: bir marta yuklanadi, keyin optimistik yangilanadi. */
export function useFavorites() {
  const auth = useAuthStore()
  const api = useApi()
  const { t } = useI18n()
  const localePath = useLocalePath()
  const toast = useToast()

  const keys = useState<string[]>('favorites', () => [])
  const loaded = useState('favorites-loaded', () => false)

  const k = (type: FavType, id: number) => `${type}:${id}`
  const has = (type: FavType, id: number) => keys.value.includes(k(type, id))

  async function load() {
    if (loaded.value || !auth.isLoggedIn) return
    loaded.value = true
    try {
      const res = await api<{ data: { type: FavType, item: { id: number } }[] }>('/favorites')
      keys.value = res.data.map(f => k(f.type, f.item.id))
    }
    catch {
      loaded.value = false
    }
  }

  async function toggle(type: FavType, id: number) {
    if (!auth.isLoggedIn) {
      await navigateTo(localePath('/kirish'))
      return
    }
    const was = has(type, id)
    keys.value = was ? keys.value.filter(x => x !== k(type, id)) : [...keys.value, k(type, id)]
    try {
      await api('/favorites', { method: was ? 'DELETE' : 'POST', body: { type, id } })
    }
    catch {
      keys.value = was ? [...keys.value, k(type, id)] : keys.value.filter(x => x !== k(type, id))
      toast.add({ title: t('search.favoriteFail'), color: 'error', icon: 'i-lucide-circle-alert' })
    }
  }

  return { has, load, toggle }
}
