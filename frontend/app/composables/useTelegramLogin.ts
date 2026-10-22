import type { User } from '~/composables/useApi'

type Status = 'idle' | 'starting' | 'waiting' | 'expired' | 'error'

/** Telegram bot orqali kirish: token olish, bot havolasi, 2 soniyalik polling. */
export function useTelegramLogin() {
  const auth = useAuthStore()
  const localePath = useLocalePath()
  const route = useRoute()
  const { campaign, referral } = useAttribution()
  const status = ref<Status>('idle')
  const botUrl = ref('')
  let timer: ReturnType<typeof setInterval> | undefined
  let busy = false

  function stop() {
    if (timer) clearInterval(timer)
    timer = undefined
  }

  async function poll(token: string) {
    if (busy) return
    busy = true
    try {
      const res = await auth.api<{ status: string, access_token?: string, user?: User }>(`/auth/telegram/status/${token}`)
      if (res.status === 'approved' && res.access_token && res.user) {
        stop()
        auth.setSession(res.access_token, res.user)
        const redirect = safeRedirect(route.query.redirect)
        if (!res.user.roles.length) await navigateTo(localePath({ path: '/boshlash', query: redirect ? { redirect } : {} }))
        else await navigateTo(redirect ?? localePath('/kabinet'))
      }
    }
    catch (e: unknown) {
      if ((e as { statusCode?: number }).statusCode === 410) {
        stop()
        status.value = 'expired'
      }
    }
    finally {
      busy = false
    }
  }

  async function start() {
    stop()
    status.value = 'starting'
    try {
      const res = await auth.api<{ token: string, bot_url: string }>('/auth/telegram/start', {
        method: 'POST',
        body: { campaign: campaign.value || undefined, referral: referral.value || undefined },
      })
      botUrl.value = res.bot_url
      status.value = 'waiting'
      window.open(res.bot_url, '_blank', 'noopener')
      timer = setInterval(() => poll(res.token), 2000)
    }
    catch {
      status.value = 'error'
    }
  }

  onBeforeUnmount(stop)

  return { status, botUrl, start }
}
