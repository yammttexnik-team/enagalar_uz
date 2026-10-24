export const WORK_FORMATS = ['hourly', 'daily', 'full_time', 'live_in'] as const
export const LANGUAGES = ['uz', 'ru', 'en', 'tg', 'kk'] as const
export const AGE_GROUPS = ['infant', 'toddler', 'preschool', 'school'] as const
export const PRICE_UNITS = ['hour', 'day', 'month'] as const

export type PriceUnit = typeof PRICE_UNITS[number]

/** Narx, vaqt va enum yorliqlarini joriy tilda formatlash. */
export function useFormat() {
  const { t, te, locale } = useI18n()
  // SSR va hydration bir xil "hozir"ni ishlatadi (aks holda "59 daqiqa" / "1 soat" farqi — hydration mismatch)
  const now = useRenderNow()

  /** 4500000 → "4 500 000" (uzilmas probel bilan). */
  function money(amount: number | string) {
    return String(Math.round(Number(amount))).replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
  }

  /** Rus tilida 3 shakl (1 год / 2 года / 5 лет), oʻzbekchada bitta. */
  function plural(key: string, n: number) {
    let index = 0
    if (locale.value === 'ru') {
      const rule = new Intl.PluralRules('ru').select(n)
      index = rule === 'one' ? 0 : rule === 'few' ? 1 : 2
    }
    return t(key, { n }, index)
  }

  function price(amount: number | null | undefined, unit?: string | null) {
    if (!amount) return t('nanny.noPrice')
    return `${money(amount)} ${t('format.som')}/${t(`format.per.${unit || 'month'}`)}`
  }

  function salary(s: { min: number | null, max: number | null, unit: string | null }) {
    if (!s.min && !s.max) return t('format.negotiable')
    const unit = `${t('format.som')}/${t(`format.per.${s.unit || 'month'}`)}`
    if (s.min && s.max && s.min !== s.max) return `${money(s.min)} – ${money(s.max)} ${unit}`
    return `${money((s.max || s.min)!)} ${unit}`
  }

  /** Brauzer Intl'i oʻzbekcha uchun ishonchsiz (SSR bilan farq qiladi), shuning uchun oʻz matnlarimiz. */
  function relativeTime(iso: string | null | undefined) {
    if (!iso) return ''
    const sec = Math.max(0, (now.value - new Date(iso).getTime()) / 1000)
    const min = Math.floor(sec / 60)
    if (min < 1) return t('format.ago.now')
    if (min < 60) return plural('format.ago.minutes', min)
    const h = Math.floor(min / 60)
    if (h < 24) return plural('format.ago.hours', h)
    const d = Math.floor(h / 24)
    if (d === 1) return t('format.ago.yesterday')
    if (d < 7) return plural('format.ago.days', d)
    if (d < 30) return plural('format.ago.weeks', Math.floor(d / 7))
    return plural('format.ago.months', Math.floor(d / 30))
  }

  const MONTHS = {
    uz: ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'],
    ru: ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'],
  }

  function date(iso: string | null | undefined) {
    if (!iso) return ''
    const d = new Date(iso)
    const months = locale.value === 'ru' ? MONTHS.ru : MONTHS.uz
    return `${d.getUTCDate()} ${months[d.getUTCMonth()]} ${d.getUTCFullYear()}`
  }

  /** enums.<group>.<value> — notanish qiymat boʻlsa, qiymatning oʻzi. */
  function enumLabel(group: 'workFormat' | 'language' | 'ageGroup' | 'priceUnit', value: string) {
    const key = `enums.${group}.${value}`
    return te(key) ? t(key) : value
  }

  /** "Dilnoza K." → "DK" */
  function initials(name: string) {
    const parts = name.replace(/\./g, '').split(/\s+/).filter(Boolean)
    return parts.slice(0, 2).map(p => p[0]!.toLocaleUpperCase()).join('')
  }

  function childAge(age: number) {
    return age === 0 ? t('format.childAgeMonths') : plural('format.childAge', age)
  }

  return { money, plural, price, salary, relativeTime, date, enumLabel, initials, childAge }
}
