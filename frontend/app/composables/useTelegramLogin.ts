import type { User } from '~/composables/useApi'

type Status = 'idle' | 'starting' | 'waiting' | 'code' | 'expired' | 'blocked' | 'error'

const STORAGE_KEY = 'enagalar.tgLogin'

/**
 * Telegram bot orqali kirish: token olish, bot havolasi, 3 soniyalik polling.
 * Bot tasdiqlagach 6 xonali kod yuboradi, foydalanuvchi uni shu yerga kiritadi
 * (havolani boshqa odamga yuborib uning akkauntiga kirib olishning oldini oladi).
 *
 * Telefonda Telegramga o'tib qaytganda sahifa qayta yuklanishi mumkin: kirish holati
 * sessionStorage'da saqlanadi va sahifa ochilganda davom ettiriladi.
 */
export function useTelegramLogin() {
  const auth = useAuthStore()
  const localePath = useLocalePath()
  const route = useRoute()
  const { campaign, referral } = useAttribution()
  const status = ref<Status>('idle')
  const botUrl = ref('')
  const confirming = ref(false)
  const codeError = ref(false)
  const attemptsLeft = ref<number | null>(null)
  let loginToken = ''
  let timer: ReturnType<typeof setInterval> | undefined
  let busy = false

  function save() {
    try {
      if (loginToken && (status.value === 'waiting' || status.value === 'code')) {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ token: loginToken, botUrl: botUrl.value, status: status.value }))
      }
      else {
        sessionStorage.removeItem(STORAGE_KEY)
      }
    }
    catch { /* private rejim: saqlamasdan ishlayveradi */ }
  }

  function stop() {
    if (timer) clearInterval(timer)
    timer = undefined
  }

  function startPolling() {
    stop()
    timer = setInterval(poll, 3000)
  }

  /** 410 va 403 kirishni tugatadi; HTTP kodini qaytaradi. */
  function fail(e: unknown) {
    const code = (e as { statusCode?: number }).statusCode
    if (code === 410 || code === 403) {
      stop()
      status.value = code === 410 ? 'expired' : 'blocked'
      save()
    }
    return code
  }

  async function poll() {
    if (busy || !loginToken) return
    busy = true
    try {
      const res = await auth.api<{ status: string }>(`/auth/telegram/status/${loginToken}`)
      if (res.status === 'awaiting_code') {
        stop()
        status.value = 'code'
        save()
      }
    }
    catch (e: unknown) {
      fail(e)
    }
    finally {
      busy = false
    }
  }

  async function confirm(code: string) {
    if (confirming.value) return
    confirming.value = true
    codeError.value = false
    try {
      const res = await auth.api<{ access_token: string, user: User }>(`/auth/telegram/status/${loginToken}/confirm`, {
        method: 'POST',
        body: { code },
      })
      loginToken = ''
      save()
      auth.setSession(res.access_token, res.user)
      const redirect = safeRedirect(route.query.redirect)
      if (!res.user.roles.length) await navigateTo(localePath({ path: '/boshlash', query: redirect ? { redirect } : {} }))
      else await navigateTo(redirect ?? localePath('/kabinet'))
    }
    catch (e: unknown) {
      const httpCode = fail(e)
      if (httpCode === 422) {
        codeError.value = true
        attemptsLeft.value = (e as { data?: { attempts_left?: number } }).data?.attempts_left ?? null
      }
      else if (httpCode !== 410 && httpCode !== 403) {
        status.value = 'error'
      }
    }
    finally {
      confirming.value = false
    }
  }

  async function start() {
    stop()
    status.value = 'starting'
    codeError.value = false
    attemptsLeft.value = null
    // Oynani bosish paytining o'zida ochamiz: iPhone Safari await'dan keyingi window.open'ni bloklaydi.
    let popup: Window | null = null
    try {
      popup = window.open('', '_blank')
    }
    catch { /* bloklangan bo'lsa pastdagi tugma qoladi */ }
    try {
      const res = await auth.api<{ token: string, bot_url: string }>('/auth/telegram/start', {
        method: 'POST',
        body: { campaign: campaign.value || undefined, referral: referral.value || undefined },
      })
      loginToken = res.token
      botUrl.value = res.bot_url
      status.value = 'waiting'
      save()
      if (popup) {
        popup.opener = null
        popup.location.href = res.bot_url
      }
      startPolling()
    }
    catch {
      popup?.close()
      status.value = 'error'
    }
  }

  // Telegramdan qaytganda kutmasdan darhol tekshiramiz.
  function onVisible() {
    if (document.visibilityState === 'visible' && status.value === 'waiting') poll()
  }

  onMounted(() => {
    document.addEventListener('visibilitychange', onVisible)
    window.addEventListener('focus', onVisible)
    // Botdagi "Saytga qaytib kodni kiritish" tugmasi: ?t=<token> — yangi oynada ham kod oynasini ochadi.
    const fromBot = typeof route.query.t === 'string' && /^[A-Za-z0-9]{20,80}$/.test(route.query.t) ? route.query.t : ''
    if (fromBot) {
      loginToken = fromBot
      status.value = 'waiting'
      poll()
      startPolling()
      return
    }
    try {
      const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null') as { token: string, botUrl: string, status: Status } | null
      if (saved?.token) {
        loginToken = saved.token
        botUrl.value = saved.botUrl
        status.value = saved.status
        if (saved.status === 'waiting') {
          poll()
          startPolling()
        }
      }
    }
    catch { /* buzilgan yozuv — oddiy boshlash */ }
  })

  onBeforeUnmount(() => {
    stop()
    document.removeEventListener('visibilitychange', onVisible)
    window.removeEventListener('focus', onVisible)
  })

  return { status, botUrl, start, confirm, confirming, codeError, attemptsLeft }
}
