import type Echo from 'laravel-echo'

/**
 * Laravel Reverb (Pusher protokoli) ulanishi. Kirgandan keyin kech (lazy) yaratiladi,
 * token oʻzgarsa qayta ulanadi, chiqishda uziladi.
 */
export default defineNuxtPlugin(() => {
  const auth = useAuthStore()
  const chat = useChatStore()
  const cfg = useRuntimeConfig().public
  const instance = shallowRef<Echo<'reverb'> | null>(null)
  let connectedFor = ''
  let version = 0

  async function ensure() {
    const token = auth.token
    const uid = auth.user?.id
    if (!token || !uid) return teardown()
    const sig = `${token}:${uid}`
    if (sig === connectedFor) return
    teardown()
    connectedFor = sig
    const v = ++version
    const [{ default: EchoClass }, { default: Pusher }] = await Promise.all([import('laravel-echo'), import('pusher-js')])
    if (v !== version) return
    ;(window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher
    const origin = new URL(cfg.apiBase, window.location.origin).origin
    const port = Number(cfg.reverbPort) || 8080
    const echo = new EchoClass({
      broadcaster: 'reverb',
      key: cfg.reverbAppKey,
      wsHost: cfg.reverbHost,
      wsPort: port,
      wssPort: port,
      forceTLS: cfg.reverbScheme === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: `${origin}/api/broadcasting/auth`,
      auth: { headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' } },
    })
    instance.value = echo
    chat.connect(echo)
  }

  function teardown() {
    version++
    connectedFor = ''
    if (instance.value) {
      chat.reset()
      instance.value.disconnect()
      instance.value = null
    }
  }

  watch(() => [auth.token, auth.user?.id] as const, ensure, { immediate: true })

  return { provide: { echo: { instance } } }
})
