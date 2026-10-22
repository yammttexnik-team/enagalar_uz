import { defineStore } from 'pinia'
import type { User } from '~/composables/useApi'

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  const nuxtApp = useNuxtApp()
  const token = useCookie<string | null>('enagalar_token', {
    maxAge: 60 * 60 * 24 * 30,
    sameSite: 'lax',
    path: '/',
    default: () => null,
  })
  const user = ref<User | null>(null)

  const api = $fetch.create({
    baseURL: config.public.apiBase,
    headers: { Accept: 'application/json' },
    onRequest({ options }) {
      const headers = new Headers(options.headers as HeadersInit | undefined)
      headers.set('Accept-Language', String(nuxtApp.$i18n.locale.value))
      if (token.value) headers.set('Authorization', `Bearer ${token.value}`)
      options.headers = headers
    },
  })

  const isLoggedIn = computed(() => !!token.value)
  const needsRole = computed(() => !!user.value && user.value.roles.length === 0)

  function setSession(accessToken: string, u: User) {
    token.value = accessToken
    user.value = u
  }

  function clear() {
    token.value = null
    user.value = null
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
