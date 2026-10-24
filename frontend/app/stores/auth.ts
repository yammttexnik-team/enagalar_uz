import { defineStore } from 'pinia'
import type { User } from '~/composables/useApi'

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  const nuxtApp = useNuxtApp()
  const token = useCookie<string | null>('enagalar_token', {
    maxAge: 60 * 60 * 24 * 30,
    sameSite: 'lax',
    secure: !import.meta.dev,
    path: '/',
    default: () => null,
  })
  const user = ref<User | null>(null)

  // SSR: API ichki tarmoq orqali (tashqi domen, Nginx va TLS aylanib o'tmaydi). Brauzerda — ochiq manzil.
  const serverBase = import.meta.server ? String(config.apiServer || '') : ''
  // SSR so'rovlari Laravel'ga mijozning IP'si bilan boradi (aks holda hamma bitta IP — umumiy rate limit)
  const clientIp = import.meta.server ? useRequestHeaders(['x-forwarded-for'])['x-forwarded-for'] : undefined
  const api = $fetch.create({
    baseURL: serverBase || config.public.apiBase,
    headers: { Accept: 'application/json' },
    onResponseError({ response }) {
      // Token eskirgan: foydalanuvchiga tegishli barcha holatni tozalaymiz
      if (response.status === 401 && token.value) clear()
    },
    onRequest({ options }) {
      const headers = new Headers(options.headers as HeadersInit | undefined)
      headers.set('Accept-Language', String(nuxtApp.$i18n.locale.value))
      if (token.value) headers.set('Authorization', `Bearer ${token.value}`)
      if (serverBase) {
        // Laravel havolalarni (masalan, imzolangan fayl URL) ochiq domen bilan yasashi uchun
        const site = new URL(config.public.siteUrl)
        headers.set('X-Forwarded-Host', site.host)
        headers.set('X-Forwarded-Proto', site.protocol.replace(':', ''))
        headers.set('X-Forwarded-Port', site.protocol === 'https:' ? '443' : '80')
      }
      if (clientIp) headers.set('X-Forwarded-For', clientIp)
      options.headers = headers
    },
  })

  const isLoggedIn = computed(() => !!token.value)
  const needsRole = computed(() => !!user.value && user.value.roles.length === 0)

  function setSession(accessToken: string, u: User) {
    token.value = accessToken
    user.value = u
  }

  /** Foydalanuvchiga tegishli umumiy holat (sevimlilar, bonus, chat) — chiqishda yoki 401 da tozalanadi. */
  function clearUserState() {
    nuxtApp.runWithContext(() => {
      useState<string[]>('favorites').value = []
      useState('favorites-loaded').value = false
      useState<number | null>('bonus-balance').value = null
      useChatStore().reset()
    })
  }

  function clear() {
    token.value = null
    user.value = null
    clearUserState()
  }

  /** Sessiyani tiklash. Token eskirgan bo'lsa tozalaydi. */
  async function fetchMe() {
    if (!token.value) return
    try {
      user.value = (await api<{ data: User }>('/me')).data
    }
    catch (e: unknown) {
      const status = (e as { statusCode?: number }).statusCode
      if (status === 401) clear()
    }
  }

  async function addRole(role: 'parent' | 'nanny') {
    user.value = (await api<{ data: User }>('/me/roles', { method: 'POST', body: { role } })).data
  }

  async function logout() {
    try {
      await api('/auth/logout', { method: 'POST' })
    }
    catch { /* token allaqachon yaroqsiz bo'lishi mumkin */ }
    clear()
  }

  return { token, user, api, isLoggedIn, needsRole, setSession, clear, fetchMe, addRole, logout }
})
