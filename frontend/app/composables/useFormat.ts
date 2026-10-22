export const WORK_FORMATS = ['hourly', 'daily', 'full_time', 'live_in'] as const
export const LANGUAGES = ['uz', 'ru', 'en', 'tg', 'kk'] as const
export const AGE_GROUPS = ['infant', 'toddler', 'preschool', 'school'] as const
export const PRICE_UNITS = ['hour', 'day', 'month'] as const

export type PriceUnit = typeof PRICE_UNITS[number]

/** Narx, vaqt va enum yorliqlarini joriy tilda formatlash. */
export function useFormat() {
  const { t, te, locale } = useI18n()

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

  function relativeTime(iso: string | null | undefined) {
    if (!iso) return ''
    const diff = (new Date(iso).getTime() - Date.now()) / 1000
    const rtf = new Intl.RelativeTimeFormat(locale.value === 'ru' ? 'ru' : 'uz', { numeric: 'auto' })
    const abs = Math.abs(diff)
    if (abs < 3600) return rtf.format(Math.round(diff / 60) || 0, 'minute')
    if (abs < 86400) return rtf.format(Math.round(diff / 3600), 'hour')
    if (abs < 86400 * 7) return rtf.format(Math.round(diff / 86400), 'day')
    if (abs < 86400 * 30) return rtf.format(Math.round(diff / (86400 * 7)), 'week')
    return rtf.format(Math.round(diff / (86400 * 30)), 'month')
  }

  function date(iso: string | null | undefined) {
    if (!iso) return ''
    return new Intl.DateTimeFormat(locale.value === 'ru' ? 'ru-RU' : 'uz-Latn', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(iso))
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
