<script setup lang="ts">
import type { ConnectionItem } from '~/composables/useRating'

const props = defineProps<{ item: ConnectionItem }>()
const emit = defineEmits<{ update: [item: ConnectionItem] }>()

const { t } = useI18n()
const localePath = useLocalePath()
const { relativeTime } = useFormat()
const { toastError, ok } = useApiError()
const { answer, review } = useConnections()

const busy = ref(false)
const stars = ref(0)
const comment = ref('')
const MAX = 1000

async function sendAnswer(worked: boolean) {
  busy.value = true
  try {
    emit('update', await answer(props.item.id, worked))
    ok(t('rating.answerSaved'))
  }
  catch (e) { toastError(e) }
  finally { busy.value = false }
}

async function sendReview() {
  if (!stars.value) return
  busy.value = true
  try {
    const r = await review(props.item.id, stars.value, comment.value)
    emit('update', { ...props.item, status: 'reviewed', my_review: { rating: r.rating, comment: r.comment, hidden: false } })
    ok(t('rating.reviewSaved'))
  }
  catch (e) { toastError(e) }
  finally { busy.value = false }
}

const otherLink = computed(() => props.item.other?.nanny_profile_id ? localePath(`/enagalar/${props.item.other.nanny_profile_id}`) : null)
</script>

<template>
  <article class="surface p-5 sm:p-6">
    <header class="flex flex-wrap items-center gap-x-3 gap-y-1">
      <h2 class="font-display text-lg font-extrabold text-highlighted">
        <NuxtLink v-if="otherLink" :to="otherLink" class="hover:underline">{{ item.other?.name }}</NuxtLink>
        <template v-else>{{ item.other?.name }}</template>
      </h2>
      <UBadge color="neutral" variant="subtle" :label="t(`rating.role.${item.other?.role ?? 'parent'}`)" />
      <span v-if="item.connected_at" class="text-sm text-muted">· {{ t('rating.connected', { when: relativeTime(item.connected_at) }) }}</span>
      <UButton class="ml-auto" size="sm" color="neutral" variant="ghost" icon="i-lucide-message-circle" :label="t('rating.openChat')" :to="localePath(`/kabinet/xabarlar/${item.conversation_id}`)" />
    </header>

    <!-- Savol -->
    <div v-if="item.status === 'question'" class="mt-4">
      <p class="font-semibold text-highlighted">{{ t('rating.question') }}</p>
      <p class="mt-1 text-sm text-muted">{{ t('rating.questionHint') }}</p>
      <div class="mt-4 flex flex-wrap gap-2">
        <UButton icon="i-lucide-check" :label="t('rating.yes')" :loading="busy" @click="sendAnswer(true)" />
        <UButton color="neutral" variant="outline" icon="i-lucide-x" :label="t('rating.no')" :disabled="busy" @click="sendAnswer(false)" />
      </div>
    </div>

    <p v-else-if="item.status === 'waiting'" class="mt-4 flex items-center gap-2 text-muted">
      <UIcon name="i-lucide-hourglass" class="size-5 shrink-0" aria-hidden="true" />{{ t('rating.waiting') }}
    </p>

    <p v-else-if="item.status === 'not_worked'" class="mt-4 flex items-center gap-2 text-muted">
      <UIcon name="i-lucide-circle-slash" class="size-5 shrink-0" aria-hidden="true" />{{ t('rating.notWorked') }}
    </p>

    <!-- Baholash formasi -->
    <form v-else-if="item.status === 'can_review'" class="mt-4 space-y-3" @submit.prevent="sendReview">
      <p class="font-semibold text-highlighted">{{ t('rating.rateTitle', { name: item.other?.name ?? '' }) }}</p>
      <RatingInput v-model="stars" />
      <UFormField :label="t('rating.comment')" :hint="t('rating.optional')" :help="t('rating.commentHelp', { n: comment.length, max: MAX })">
        <UTextarea v-model="comment" class="w-full" :rows="3" :maxlength="MAX" :placeholder="t('rating.commentPlaceholder')" />
      </UFormField>
      <UButton type="submit" icon="i-lucide-send" :label="t('rating.submit')" :loading="busy" :disabled="!stars" />
    </form>

    <div v-else-if="item.my_review" class="mt-4">
      <p class="flex flex-wrap items-center gap-2 text-sm text-muted">
        {{ t('rating.yourReview') }} <RatingStars :value="item.my_review.rating" />
        <UBadge v-if="item.my_review.hidden" color="error" variant="subtle" :label="t('rating.hidden')" />
      </p>
      <p v-if="item.my_review.comment" class="mt-2 whitespace-pre-line text-default">{{ item.my_review.comment }}</p>
    </div>
  </article>
</template>
