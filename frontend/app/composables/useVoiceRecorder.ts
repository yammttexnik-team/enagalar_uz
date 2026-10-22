/** MediaRecorder bilan ovoz yozish (Safari — audio/mp4, qolganlari — audio/webm). */
export function useVoiceRecorder(onDone: (blob: Blob, seconds: number, ext: string) => void) {
  const { t } = useI18n()
  const toast = useToast()
  const recording = ref(false)
  const seconds = ref(0)
  let recorder: MediaRecorder | null = null
  let stream: MediaStream | null = null
  let chunks: Blob[] = []
  let timer: ReturnType<typeof setInterval> | undefined
  let started = 0
  let cancelled = false

  const MIMES = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus']
  const supported = () => import.meta.client && typeof MediaRecorder !== 'undefined' && !!navigator.mediaDevices?.getUserMedia

  function release() {
    clearInterval(timer)
    stream?.getTracks().forEach(tr => tr.stop())
    stream = null
    recording.value = false
  }

  async function start() {
    if (recording.value) return
    if (!supported()) {
      toast.add({ title: t('chat.voiceUnsupported'), color: 'warning', icon: 'i-lucide-mic-off' })
      return
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    }
    catch {
      toast.add({ title: t('chat.micDenied'), color: 'warning', icon: 'i-lucide-mic-off' })
      return
    }
    const mime = MIMES.find(m => MediaRecorder.isTypeSupported(m))
    recorder = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined)
    chunks = []
    cancelled = false
    recorder.ondataavailable = e => e.data.size && chunks.push(e.data)
    recorder.onstop = () => {
      const type = (recorder?.mimeType || mime || 'audio/webm').split(';')[0]!
      const secs = Math.max(1, Math.min(MAX_VOICE_SECONDS, Math.round((Date.now() - started) / 1000)))
      release()
      if (!cancelled && chunks.length) onDone(new Blob(chunks, { type }), secs, type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm')
    }
    started = Date.now()
    seconds.value = 0
    recorder.start(250)
    recording.value = true
    timer = setInterval(() => {
      seconds.value = Math.floor((Date.now() - started) / 1000)
      if (seconds.value >= MAX_VOICE_SECONDS) stop()
    }, 250)
  }

  function stop() {
    if (recorder && recorder.state !== 'inactive') recorder.stop()
  }

  function cancel() {
    cancelled = true
    if (recorder && recorder.state !== 'inactive') recorder.stop()
    else release()
  }

  onBeforeUnmount(cancel)
  return { recording, seconds, start, stop, cancel, supported }
}
