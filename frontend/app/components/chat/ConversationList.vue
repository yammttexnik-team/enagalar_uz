<script setup lang="ts">
defineProps<{ activeId?: number | null }>()
const { t } = useI18n()
const chat = useChatStore()
const localePath = useLocalePath()
const { listTime } = useChatFormat()
const previewText = useChatPreview()
const q = ref('')

onMounted(() => {
  chat.fetchConversations()
})

const filtered = computed(() => {
  const s = q.value.trim().toLowerCase()
  if (!s) return chat.sorted
  return chat.sorted.filter(c => `${c.other?.name ?? ''} ${c.vacancy?.title ?? ''}`.toLowerCase().includes(s))
})

const isRead = (c: Conversation) => !!c.last_message && (chat.otherRead[c.id] ?? 0) >= c.last_message.id
</script>

<template>
  <section class="flex min-h-0 flex-col border-default lg:border-r" :aria-label="t('chat.title')">
    <div class="space-y-3 border-b border-default px-4 pb-3 pt-4">
      <h1 class="h3-title text-highlighted">{{ t('chat.title') }}</h1>
      <UInput
        v-model="q"
        type="search"
        icon="i-lucide-search"
        size="lg"
        class="w-full"
        :placeholder="t('chat.search')"
        :aria-label="t('chat.search')"
      />
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto">
      <div v-if="!chat.list.loaded && chat.list.loading" class="space-y-1 p-3" aria-busy="true">
        <div v-for="i in 5" :key="i" class="flex animate-pulse items-center gap-3 rounded-2xl p-3">
          <div class="size-12 rounded-full bg-elevated" />
          <div class="flex-1 space-y-2"><div class="h-3.5 w-1/2 rounded bg-elevated" /><div class="h-3 w-4/5 rounded bg-elevated" /></div>
        </div>
      </div>

      <div v-else-if="chat.list.error && !chat.sorted.length" class="px-6 py-12 text-center" role="alert">
        <p class="text-muted">{{ t('dashboard.common.error') }}</p>
        <UButton class="mt-4" icon="i-lucide-rotate-cw" :label="t('search.retry')" @click="chat.fetchConversations()" />
      </div>

      <div v-else-if="!chat.sorted.length" class="flex flex-col items-center px-8 py-14 text-center">
        <span class="grid size-14 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-200">
          <UIcon name="i-lucide-messages-square" class="size-7" aria-hidden="true" />
        </span>
        <p class="mt-4 max-w-xs text-muted">{{ t('chat.empty') }}</p>
        <UButton class="mt-5" :to="localePath('/enagalar')" :label="t('home.hero.ctaNanny')" />
      </div>

      <p v-else-if="!filtered.length" class="px-6 py-10 text-center text-muted">{{ t('chat.noResults') }}</p>

      <ul v-else class="p-2">
        <li v-for="c in filtered" :key="c.id">
          <NuxtLink
            :to="localePath(`/kabinet/xabarlar/${c.id}`)"
            :aria-current="activeId === c.id ? 'page' : undefined"
            class="flex items-center gap-3 rounded-2xl px-3 py-3 transition-colors focus-visible:outline-2 focus-visible:outline-primary"
            :class="activeId === c.id ? 'bg-brand-50 dark:bg-brand-900/60' : 'hover:bg-elevated'"
          >
            <ChatAvatar :initials="c.other?.initials ?? '?'" :id="c.other?.id ?? c.id" :online="chat.isOnline(c.other?.id)" />
            <div class="min-w-0 flex-1">
              <div class="flex items-baseline justify-between gap-2">
                <p class="flex min-w-0 items-center gap-1 font-display text-[15px] font-extrabold text-highlighted">
                  <span class="truncate">{{ c.other?.name }}</span>
                  <UIcon v-if="c.other?.verified" name="i-lucide-badge-check" class="size-4 shrink-0 text-brand-500" :aria-label="t('chat.verified')" role="img" />
                  <UIcon v-if="c.muted" name="i-lucide-bell-off" class="size-3.5 shrink-0 text-dimmed" :aria-label="t('chat.muted')" role="img" />
                </p>
                <time class="shrink-0 text-xs" :class="c.unread_count ? 'font-semibold text-brand-600 dark:text-brand-300' : 'text-muted'" :datetime="c.last_message?.created_at ?? undefined">{{ listTime(c.last_message?.created_at) }}</time>
              </div>
              <p v-if="c.vacancy" class="truncate text-xs text-muted">{{ c.vacancy.title }}</p>
              <div class="mt-0.5 flex items-center justify-between gap-2">
                <p class="flex min-w-0 items-center gap-1 text-sm" :class="c.unread_count ? 'font-medium text-highlighted' : 'text-muted'">
                  <template v-if="c.last_message?.sender_is_me">
                    <UIcon :name="isRead(c) ? 'i-lucide-check-check' : 'i-lucide-check'" class="size-4 shrink-0" :class="isRead(c) ? 'text-brand-500' : 'text-dimmed'" aria-hidden="true" />
                    <span class="sr-only">{{ isRead(c) ? t('chat.read') : t('chat.sent') }}</span>
                  </template>
                  <span class="truncate">{{ previewText(c.last_message?.type, c.last_message?.preview) }}</span>
                </p>
                <span
                  v-if="c.unread_count"
                  class="grid min-w-5 shrink-0 place-items-center rounded-full px-1.5 py-0.5 text-xs font-bold text-white"
                  :class="c.muted ? 'bg-sand-400 dark:bg-sand-600' : 'bg-brand-500 dark:bg-brand-400 dark:text-sand-900'"
                  :aria-label="t('chat.unreadCount', { n: c.unread_count })"
                >{{ c.unread_count > 99 ? '99+' : c.unread_count }}</span>
              </div>
            </div>
          </NuxtLink>
        </li>
        <li v-if="chat.list.hasMore" class="p-2 text-center">
          <UButton color="neutral" variant="ghost" :loading="chat.list.loading" :label="t('chat.loadMore')" @click="chat.fetchConversations(true)" />
        </li>
      </ul>
    </div>
  </section>
</template>
