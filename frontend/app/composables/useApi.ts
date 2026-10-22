/** Laravel API'ga so'rov yuborish uchun umumiy $fetch. */
export function useApi() {
  const config = useRuntimeConfig()
  const { locale } = useI18n()

  return $fetch.create({
    baseURL: config.public.apiBase,
    headers: { Accept: 'application/json' },
    onRequest({ options }) {
      options.headers.set('Accept-Language', locale.value)
    },
  })
}

export interface LocalizedName {
  uz: string
  uz_cyrl?: string
  ru: string
}

export interface District {
  id: number
  slug: string
  name: LocalizedName
}

export interface Region extends District {
  districts: District[]
}

export function useLocalizedName() {
  const { locale } = useI18n()

  return (name: LocalizedName) => (locale.value === 'ru' ? name.ru : name.uz)
}
