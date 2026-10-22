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

export interface Badge {
  slug: string
  name: LocalizedName
  description: { uz: string | null, ru: string | null }
  icon: string
  color: string
}

export interface User {
  id: number
  name: string | null
  phone: string | null
  telegram_username: string | null
  locale: string | null
  roles: string[]
  badges: Badge[]
}

/** Laravel API'ga so'rov yuborish uchun umumiy $fetch (kirgan bo'lsa Bearer token bilan). */
export function useApi() {
  return useAuthStore().api
}

export function useLocalizedName() {
  const { locale } = useI18n()

  return (name: LocalizedName) => (locale.value === 'ru' ? name.ru : name.uz)
}
