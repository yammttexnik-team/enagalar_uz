import type { User } from '~/composables/useApi'

type Status = 'idle' | 'starting' | 'waiting' | 'code' | 'expired' | 'blocked' | 'error'

/**
 * Telegram bot orqali kirish: token olish, bot havolasi, 2 soniyalik polling.
 * Bot tasdiqlagach 6 xonali kod yuboradi, foydalanuvchi uni shu yerga kiritadi
 * (havolani boshqa odamga yuborib uning akkauntiga kirib olishning oldini oladi).
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

  function stop() {
    if (timer) clearInterval(timer)
    timer = undefined
  }

  /** 410 va 403 kirishni tugatadi; HTTP kodini qaytaradi. */
  function fail(e: unknown) {
    const code = (e as { statusCode?: number }).statusCode
    if (code === 410 || code === 403) {
      stop()
      status.value = code === 410 ? 'expired' : 'blocked'
    }
    return code
  }

  async function poll() {
    if (busy) return
    busy = true
    try {
      const res = await auth.api<{ status: string }>(`/auth/telegram/status/${loginToken}`)
      if (res.status === 'awaiting_code') {
        stop()
        status.value = 'code'
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
    try {
      const res = await auth.api<{ token: string, bot_url: string }>('/auth/telegram/start', {
        method: 'POST',
        body: { campaign: campaign.value || undefined, referral: referral.value || undefined },
      })
      loginToken = res.token
      botUrl.value = res.bot_url
      status.value = 'waiting'
      window.open(res.bot_url, '_blank', 'noopener')
      timer = setInterval(poll, 2000)
    }
    catch {
      status.value = 'error'
    }
  }

  onBeforeUnmount(stop)

  return { status, botUrl, start, confirm, confirming, codeError, attemptsLeft }
}
