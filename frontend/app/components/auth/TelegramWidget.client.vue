<script setup lang="ts">
/** Rasmiy Telegram Login vidjeti: tugma → Telegram'da "Confirm" → imzolangan ma'lumot onAuth orqali keladi. */
const props = defineProps<{ bot: string, lang?: string }>()
const emit = defineEmits<{ auth: [data: Record<string, string | number>] }>()
const failed = ref(false)
const boxId = `tg-login-${Math.random().toString(36).slice(2, 8)}`

onMounted(async () => {
  await nextTick()
  const box = document.getElementById(boxId)
  if (!box) return
  const w = window as unknown as Record<string, unknown>
  w.onEnagalarTelegramAuth = (data: Record<string, string | number>) => emit('auth', data)
  const s = document.createElement('script')
  s.src = 'https://telegram.org/js/telegram-widget.js?22'
  s.async = true
  s.setAttribute('data-telegram-login', props.bot)
  s.setAttribute('data-size', 'large')
  s.setAttribute('data-radius', '12')
  s.setAttribute('data-request-access', 'write')
  if (props.lang) s.setAttribute('data-lang', props.lang)
  s.setAttribute('data-onauth', 'onEnagalarTelegramAuth(user)')
  s.onerror = () => { failed.value = true }
  box.appendChild(s)
})

onBeforeUnmount(() => {
  delete (window as unknown as Record<string, unknown>).onEnagalarTelegramAuth
})
</script>

<template>
  <div v-show="!failed" :id="boxId" class="flex min-h-12 justify-center" />
</template>
