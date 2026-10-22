/** ?c=KAMPANIYA va ?r=TAKLIF_KODI istalgan sahifada 30 kunga cookie'ga yoziladi (kirishda backendga yuboriladi). */
const opts = { maxAge: 60 * 60 * 24 * 30, sameSite: 'lax' as const, path: '/' }

function first(v: unknown): string {
  const s = Array.isArray(v) ? v[0] : v
  return typeof s === 'string' ? s : ''
}

export default defineNuxtRouteMiddleware((to) => {
  const c = first(to.query.c)
  if (/^[A-Za-z0-9_]{1,32}$/.test(c)) useCookie<string | null>('enagalar_c', opts).value = c.toUpperCase()

  const r = first(to.query.r)
  if (/^[A-Za-z0-9]{8}$/.test(r)) useCookie<string | null>('enagalar_r', opts).value = r.toUpperCase()
})
