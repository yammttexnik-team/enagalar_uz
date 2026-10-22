<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

const props = defineProps<{ conversationId: number }>()
const { t } = useI18n()
const chat = useChatStore()
const auth = useAuthStore()
const toast = useToast()
const localePath = useLocalePath()
const { relativeTime } = useFormat()
const { dayLabel, dayKey } = useChatFormat()
const { toastError, ok } = useApiError()

const id = computed(() => props.conversationId)
const conv = computed(() => chat.conversations[id.value])
const th = computed(() => chat.thread(id.value))
const me = computed(() => auth.user?.id ?? 0)
const { typing, sendTyping } = useChatRealtime(id)

// --- Sarlavha holati ---
const status = computed(() => {
  const o = conv.value?.other
  if (!o) return ''
  if (typing.value) return t('chat.typing')
  if (chat.isOnline(o.id)) return t('chat.online')
  return o.last_seen_at ? t('chat.lastSeen', { when: relativeTime(o.last_seen_at) }) : t('chat.offline')
})
const profileLink = computed(() => {
  const o = conv.value?.other
  if (o?.role === 'nanny' && o.nanny_profile_id) return localePath(`/enagalar/${o.nanny_profile_id}`)
  if (conv.value?.vacancy) return localePath(`/vakansiyalar/${conv.value.vacancy.id}`)
  return null
})

const menu = computed<DropdownMenuItem[][]>(() => {
  const c = conv.value
  if (!c) return []
  const top: DropdownMenuItem[] = []
  if (profileLink.value) top.push({ label: t('chat.menu.profile'), icon: 'i-lucide-user-round', to: profileLink.value })
  top.push({ label: c.muted ? t('chat.menu.unmute') : t('chat.menu.mute'), icon: c.muted ? 'i-lucide-bell' : 'i-lucide-bell-off', onSelect: toggleMute })
  return [top, [
    c.blocked_by_me
      ? { label: t('chat.menu.unblock'), icon: 'i-lucide-user-check', onSelect: () => unblock() }
      : { label: t('chat.menu.block'), icon: 'i-lucide-ban', color: 'error' as const, onSelect: () => (blockOpen.value = true) },
    { label: t('chat.menu.report'), icon: 'i-lucide-flag', color: 'error' as const, onSelect: () => (reportOpen.value = true) },
  ]]
})

async function toggleMute() {
  try { await chat.setMuted(id.value, !conv.value!.muted) }
  catch (e) { toastError(e) }
}
const blockOpen = ref(false)
const reportOpen = ref(false)
const busy = ref(false)
async function block() {
  busy.value = true
  try { await chat.setBlocked(id.value, true); blockOpen.value = false; ok(t('chat.menu.blocked')) }
  catch (e) { toastError(e) }
  finally { busy.value = false }
}
async function unblock() {
  busy.value = true
  try { await chat.setBlocked(id.value, false) }
  catch (e) { toastError(e) }
  finally { busy.value = false }
}

// --- Xabarlar roʻyxati ---
const rows = computed(() => {
  const out: ({ kind: 'day', key: string, label: string } | { kind: 'msg', key: string, m: ChatMessage })[] = []
  let last = ''
  for (const m of th.value.items) {
    const k = dayKey(m.created_at)
    if (k !== last) { out.push({ kind: 'day', key: `d-${k}`, label: dayLabel(m.created_at) }); last = k }
    out.push({ kind: 'msg', key: messageKey(m), m })
  }
  return out
})
const byId = computed(() => new Map(th.value.items.map(m => [m.id, m])))
const authorOf = (senderId: number | null | undefined) => (senderId === me.value ? t('chat.you') : conv.value?.other?.name ?? '')

const scroller = ref<HTMLElement>()
const content = ref<HTMLElement>()
const atBottom = ref(true)
const newCount = ref(0)
const announce = ref('')

function scrollToBottom(smooth = false) {
  const el = scroller.value
  if (!el) return
  el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'auto' })
  newCount.value = 0
}

async function loadOlder() {
  const el = scroller.value
  if (!el || th.value.loading || !th.value.hasMore) return
  const prevH = el.scrollHeight
  const prevT = el.scrollTop
  await chat.loadMessages(id.value, true)
  await nextTick()
  el.scrollTop = el.scrollHeight - prevH + prevT
}

function onScroll() {
  const el = scroller.value
  if (!el) return
  atBottom.value = el.scrollHeight - el.scrollTop - el.clientHeight < 80
  if (atBottom.value) newCount.value = 0
  if (el.scrollTop < 150) loadOlder()
}

async function init() {
  if (!conv.value) await chat.fetchConversation(id.value)
  if (th.value.loaded) chat.loadMessages(id.value).then(() => {}) // yangilash
  else await chat.loadMessages(id.value)
  await nextTick()
  scrollToBottom()
}
onMounted(init)

// Yangi xabar: pastda boʻlsak yoki oʻzimizniki boʻlsa — pastga; aks holda "↓ yangi xabarlar"
watch(() => th.value.items.at(-1) && messageKey(th.value.items.at(-1)!), async (k, old) => {
  const last = th.value.items.at(-1)
  if (!k || !old || !last) return
  const mine = last.sender_id === me.value
  if (!mine) announce.value = `${conv.value?.other?.name}: ${last.body ?? (last.type === 'image' ? t('chat.photo') : t('chat.voiceMessage'))}`
  if (mine || atBottom.value) { await nextTick(); scrollToBottom(true) }
  else newCount.value++
})

// --- Oʻqildi: koʻrinib turgan eng katta id (debounce) ---
const visible = new Set<number>()
let io: IntersectionObserver | undefined
let readTimer: ReturnType<typeof setTimeout> | undefined
function scheduleRead() {
  clearTimeout(readTimer)
  readTimer = setTimeout(() => {
    if (document.visibilityState !== 'visible' || !visible.size) return
    chat.markRead(id.value, Math.max(...visible))
  }, 400)
}
function observe() {
  if (!io || !scroller.value) return
  scroller.value.querySelectorAll('[data-incoming][data-mid]').forEach(el => io!.observe(el))
}
onMounted(() => {
  io = new IntersectionObserver((entries) => {
    for (const e of entries) {
      const mid = Number((e.target as HTMLElement).dataset.mid)
      if (e.isIntersecting) visible.add(mid)
      else visible.delete(mid)
    }
    scheduleRead()
  }, { root: scroller.value, threshold: 0.6 })
  document.addEventListener('visibilitychange', scheduleRead)
})
// Tarkib yoki oyna oʻlchami oʻzgarsa (rasm, klaviatura, yozuv paneli) va pastda boʻlsak — pastda qolamiz
let ro: ResizeObserver | undefined
onMounted(() => {
  ro = new ResizeObserver(() => { if (atBottom.value && scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight })
  if (scroller.value) ro.observe(scroller.value)
  if (content.value) ro.observe(content.value)
})
onBeforeUnmount(() => { ro?.disconnect(); io?.disconnect(); clearTimeout(readTimer); document.removeEventListener('visibilitychange', scheduleRead) })
watch(() => th.value.items.length, () => nextTick(observe), { flush: 'post' })
watch(() => th.value.loaded, () => nextTick(observe), { flush: 'post' })

// --- Javobga sakrash ---
const flash = ref<number | null>(null)
async function jump(mid: number) {
  for (let i = 0; i < 6; i++) {
    const el = document.getElementById(`m-${mid}`)
    if (el) {
      el.scrollIntoView({ block: 'center', behavior: 'smooth' })
      flash.value = mid
      setTimeout(() => { if (flash.value === mid) flash.value = null }, 1600)
      return
    }
    if (!th.value.hasMore) return
    await loadOlder()
  }
}

// --- Amallar ---
const replyTo = ref<ChatMessage | null>(null)
const editing = ref<ChatMessage | null>(null)
const composer = ref<{ focus: () => void }>()
const deleteTarget = ref<ChatMessage | null>(null)
const lightbox = ref<ChatMessage | null>(null)
const lightboxOpen = computed({ get: () => !!lightbox.value, set: (v) => { if (!v) lightbox.value = null } })
const sheet = ref<ChatMessage | null>(null)
let sheetAt = 0
const sheetOpen = computed({
  get: () => !!sheet.value,
  // Uzoq bosishdan keyin barmoq koʻtarilganda varaqa darrov yopilib qolmasligi uchun
  set: (v) => { if (!v && Date.now() - sheetAt > 500) sheet.value = null },
})
watch(sheet, (m) => { if (m) sheetAt = Date.now() })
const blocked = computed(() => !!conv.value && (conv.value.blocked_by_me || conv.value.blocked_me))

const canEdit = (m: ChatMessage) => m.sender_id === me.value && m.type === 'text' && !m.deleted && Date.now() - new Date(m.created_at).getTime() < 24 * 3600 * 1000

function menuItems(m: ChatMessage): DropdownMenuItem[] {
  if (m.deleted || m.pending) return []
  const mine = m.sender_id === me.value
  const items: DropdownMenuItem[] = [{ label: t('chat.actions.reply'), icon: 'i-lucide-reply', onSelect: () => startReply(m) }]
  if (m.body) items.push({ label: t('chat.actions.copy'), icon: 'i-lucide-copy', onSelect: () => copy(m) })
  if (canEdit(m)) items.push({ label: t('chat.actions.edit'), icon: 'i-lucide-pencil', onSelect: () => startEdit(m) })
  if (mine) items.push({ label: t('chat.actions.delete'), icon: 'i-lucide-trash-2', color: 'error', onSelect: () => (deleteTarget.value = m) })
  return blocked.value ? items.filter(i => i.icon !== 'i-lucide-reply' && i.icon !== 'i-lucide-pencil') : items
}

function startReply(m: ChatMessage) { editing.value = null; replyTo.value = m; composer.value?.focus() }
function startEdit(m: ChatMessage) { replyTo.value = null; editing.value = m }
async function copy(m: ChatMessage) {
  try { await navigator.clipboard.writeText(m.body ?? ''); toast.add({ title: t('chat.actions.copied'), icon: 'i-lucide-check', color: 'success' }) }
  catch { toast.add({ title: t('dashboard.common.error'), color: 'error' }) }
}
async function doDelete() {
  const m = deleteTarget.value
  if (!m) return
  busy.value = true
  try { await chat.remove(m); deleteTarget.value = null }
  catch (e) { toastError(e) }
  finally { busy.value = false }
}
const deleteOpen = computed({ get: () => !!deleteTarget.value, set: (v) => { if (!v) deleteTarget.value = null } })

// --- Ogohlantirish: aloqa maʼlumoti ---
const warnKey = computed(() => `chat-warn-${id.value}`)
const warnDismissed = ref(false)
onMounted(() => { try { warnDismissed.value = localStorage.getItem(warnKey.value) === '1' } catch { /* private rejim */ } })
const showWarn = computed(() => !warnDismissed.value && th.value.items.some(m => m.meta?.contact_warning))
function dismissWarn() {
  warnDismissed.value = true
  try { localStorage.setItem(warnKey.value, '1') } catch { /* private rejim */ }
}
</script>

<template>
  <section class="relative flex h-full min-h-0 flex-col bg-default max-lg:fixed max-lg:inset-0 max-lg:z-50" :aria-label="conv?.other?.name || t('chat.title')">
    <div v-if="chat.missing[id]" class="grid flex-1 place-items-center px-6 text-center">
      <div>
        <p class="text-muted">{{ t('chat.notFound') }}</p>
        <UButton class="mt-4" :to="localePath('/kabinet/xabarlar')" :label="t('chat.backToList')" />
      </div>
    </div>

    <template v-else>
      <!-- Sarlavha -->
      <header class="flex items-center gap-2 border-b border-default bg-default px-2 py-2.5 pt-[max(0.625rem,env(safe-area-inset-top))] sm:px-4">
        <UButton :to="localePath('/kabinet/xabarlar')" color="neutral" variant="ghost" size="lg" icon="i-lucide-arrow-left" class="lg:hidden" :aria-label="t('chat.back')" />
        <template v-if="conv?.other">
          <ChatAvatar :initials="conv.other.initials" :id="conv.other.id" :online="chat.isOnline(conv.other.id)" size="sm" />
          <div class="min-w-0 flex-1">
            <h1 class="flex items-center gap-1 truncate font-display text-base font-extrabold text-highlighted">
              <span class="truncate">{{ conv.other.name }}</span>
              <UIcon v-if="conv.other.verified" name="i-lucide-badge-check" class="size-4 shrink-0 text-brand-500" role="img" :aria-label="t('chat.verified')" />
            </h1>
            <p class="truncate text-xs" :class="typing ? 'font-semibold text-brand-600 dark:text-brand-300' : chat.isOnline(conv.other.id) ? 'text-leaf-600 dark:text-leaf-400' : 'text-muted'" aria-live="off">{{ status }}</p>
          </div>
          <NuxtLink
            v-if="conv.vacancy"
            :to="localePath(`/vakansiyalar/${conv.vacancy.id}`)"
            class="hidden max-w-52 items-center gap-1.5 truncate rounded-full bg-apricot-50 px-3 py-1.5 text-xs font-semibold text-apricot-700 hover:bg-apricot-100 sm:inline-flex dark:bg-apricot-950 dark:text-apricot-200"
          >
            <UIcon name="i-lucide-megaphone" class="size-3.5 shrink-0" aria-hidden="true" /><span class="truncate">{{ conv.vacancy.title }}</span>
          </NuxtLink>
          <UDropdownMenu :items="menu" :content="{ align: 'end' }">
            <UButton color="neutral" variant="ghost" size="lg" icon="i-lucide-ellipsis-vertical" :aria-label="t('chat.menu.label')" />
          </UDropdownMenu>
        </template>
        <div v-else class="flex flex-1 animate-pulse items-center gap-3"><div class="size-10 rounded-full bg-elevated" /><div class="h-4 w-32 rounded bg-elevated" /></div>
      </header>
      <NuxtLink
        v-if="conv?.vacancy"
        :to="localePath(`/vakansiyalar/${conv.vacancy.id}`)"
        class="flex items-center gap-2 truncate border-b border-default bg-apricot-50 px-4 py-1.5 text-xs font-semibold text-apricot-700 sm:hidden dark:bg-apricot-950 dark:text-apricot-200"
      >
        <UIcon name="i-lucide-megaphone" class="size-3.5 shrink-0" aria-hidden="true" /><span class="truncate">{{ conv.vacancy.title }}</span>
      </NuxtLink>

      <!-- Xavfsizlik ogohlantirishi -->
      <div v-if="showWarn" class="flex items-start gap-3 border-b border-honey-200 bg-honey-50 px-4 py-3 text-sm text-honey-800 dark:border-honey-800 dark:bg-honey-950 dark:text-honey-200" role="note">
        <UIcon name="i-lucide-shield-alert" class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
        <p class="flex-1 text-xs leading-relaxed sm:text-sm"><strong class="font-bold">{{ t('chat.warning.title') }}</strong> {{ t('chat.warning.text') }}</p>
        <UButton color="neutral" variant="ghost" size="xs" icon="i-lucide-x" :aria-label="t('chat.warning.dismiss')" @click="dismissWarn" />
      </div>

      <!-- Xabarlar -->
      <div class="relative min-h-0 flex-1">
        <div ref="scroller" class="h-full overflow-y-auto overscroll-contain bg-brand-50/50 px-3 py-4 sm:px-5 dark:bg-brand-950/30" @scroll.passive="onScroll">
         <div ref="content">
          <div v-if="th.loading && !th.loaded" class="space-y-3" aria-busy="true">
            <div v-for="i in 5" :key="i" class="flex animate-pulse" :class="i % 2 ? 'justify-start' : 'justify-end'"><div class="h-12 rounded-2xl bg-elevated" :style="{ width: `${40 + (i * 13) % 35}%` }" /></div>
          </div>
          <div v-else-if="th.error && !th.items.length" class="py-16 text-center" role="alert">
            <p class="text-muted">{{ t('dashboard.common.error') }}</p>
            <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="chat.loadMessages(id)" />
          </div>
          <template v-else>
            <p v-if="th.loading" class="pb-3 text-center text-xs text-muted"><UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" aria-hidden="true" /></p>
            <div v-if="th.loaded && !th.items.length" class="grid place-items-center py-16 text-center">
              <div>
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200"><UIcon name="i-lucide-hand-heart" class="size-7" aria-hidden="true" /></span>
                <p class="mt-3 text-muted">{{ t('chat.startHint') }}</p>
              </div>
            </div>
            <ul class="space-y-1.5">
              <template v-for="r in rows" :key="r.key">
                <li v-if="r.kind === 'day'" class="sticky top-0 z-10 flex justify-center py-2" role="separator" :aria-label="r.label">
                  <span class="rounded-full bg-sand-200/90 px-3 py-1 text-xs font-semibold text-sand-700 backdrop-blur dark:bg-sand-700/90 dark:text-sand-100">{{ r.label }}</span>
                </li>
                <ChatMessageBubble
                  v-else
                  :message="r.m"
                  :mine="r.m.sender_id === me"
                  :items="menuItems(r.m)"
                  :reply-author="authorOf(r.m.reply_to?.sender_id)"
                  :reply-deleted="!!r.m.reply_to && !!byId.get(r.m.reply_to.id)?.deleted"
                  :highlighted="flash === r.m.id"
                  @image="lightbox = $event"
                  @jump="jump"
                  @sheet="sheet = $event"
                  @retry="chat.retry($event)"
                  @discard="chat.discard($event)"
                />
              </template>
            </ul>
            <p v-if="typing" class="mt-2 flex items-center gap-1.5 text-sm text-muted" aria-hidden="true">
              <span class="inline-flex gap-0.5"><i v-for="d in 3" :key="d" class="size-1.5 animate-bounce rounded-full bg-brand-400" :style="{ animationDelay: `${d * 120}ms` }" /></span>{{ t('chat.typing') }}
            </p>
          </template>
         </div>
        </div>

        <Transition enter-active-class="transition duration-150" enter-from-class="translate-y-2 opacity-0" leave-active-class="transition duration-100" leave-to-class="opacity-0">
          <button
            v-if="!atBottom && (newCount || th.items.length > 8)"
            type="button"
            class="absolute bottom-3 right-4 flex items-center gap-1.5 rounded-full bg-brand-500 px-3.5 py-2 text-sm font-semibold text-white shadow-lg hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 dark:bg-brand-400 dark:text-sand-900"
            @click="scrollToBottom(true)"
          >
            <UIcon name="i-lucide-arrow-down" class="size-4" aria-hidden="true" />{{ newCount ? t('chat.newMessages', { n: newCount }) : t('chat.toLatest') }}
          </button>
        </Transition>
        <div class="sr-only" aria-live="polite" aria-atomic="true">{{ announce }}</div>
      </div>

      <!-- Pastki qism -->
      <div v-if="conv?.blocked_by_me" class="flex flex-col items-center gap-3 border-t border-default px-4 py-4 text-center sm:flex-row sm:justify-between sm:text-left">
        <p class="flex items-center gap-2 text-sm text-muted"><UIcon name="i-lucide-ban" class="size-5 shrink-0" aria-hidden="true" />{{ t('chat.blockedByMe') }}</p>
        <UButton color="neutral" variant="outline" icon="i-lucide-user-check" :loading="busy" :label="t('chat.menu.unblock')" @click="unblock" />
      </div>
      <div v-else-if="conv?.blocked_me" class="flex items-center justify-center gap-2 border-t border-default px-4 py-5 text-sm text-muted">
        <UIcon name="i-lucide-ban" class="size-5 shrink-0" aria-hidden="true" />{{ t('chat.blockedMe') }}
      </div>
      <ChatComposer
        v-else-if="conv"
        ref="composer"
        :conversation-id="id"
        :reply-to="replyTo"
        :editing="editing"
        :reply-author="authorOf(replyTo?.sender_id)"
        @cancel-reply="replyTo = null"
        @cancel-edit="editing = null"
        @typing="sendTyping"
        @sent="scrollToBottom(true)"
      />
    </template>

    <!-- Rasm koʻrish -->
    <UModal v-model:open="lightboxOpen" :title="t('chat.photo')" :description="lightbox?.body || t('chat.photo')" :ui="{ content: 'max-w-4xl bg-transparent shadow-none ring-0 divide-y-0' }">
      <template #content>
        <div class="relative flex justify-center">
          <img v-if="lightbox?.attachment" :src="lightbox.attachment.url" :alt="lightbox.body || t('chat.photo')" class="max-h-[85dvh] max-w-full rounded-2xl object-contain">
          <UButton class="absolute right-2 top-2 rounded-full" color="neutral" icon="i-lucide-x" :aria-label="t('search.close')" @click="lightbox = null" />
        </div>
      </template>
    </UModal>

    <!-- Mobil: xabar amallari -->
    <UDrawer v-model:open="sheetOpen" direction="bottom" :title="t('chat.messageActions')" :description="sheet?.body ?? ''">
      <template #body>
        <ul class="space-y-1 pb-4">
          <li v-for="(it, i) in (sheet ? menuItems(sheet) : [])" :key="i">
            <button
              type="button"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-base font-medium hover:bg-elevated"
              :class="it.color === 'error' ? 'text-pomegranate-600 dark:text-pomegranate-300' : 'text-highlighted'"
              @click="(it as any).onSelect?.(); sheet = null"
            >
              <UIcon :name="it.icon as string" class="size-5" aria-hidden="true" />{{ it.label }}
            </button>
          </li>
        </ul>
      </template>
    </UDrawer>

    <DashboardConfirm v-model:open="deleteOpen" :title="t('chat.actions.deleteTitle')" :text="t('chat.actions.deleteText')" :confirm="t('chat.actions.delete')" color="error" :loading="busy" @confirm="doDelete" />
    <DashboardConfirm v-model:open="blockOpen" :title="t('chat.menu.blockTitle')" :text="t('chat.menu.blockText')" :confirm="t('chat.menu.block')" color="error" :loading="busy" @confirm="block" />
    <ChatReportModal v-model:open="reportOpen" :conversation-id="id" />
  </section>
</template>
