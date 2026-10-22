<template>
  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2" aria-hidden="true">
    <div v-for="i in 6" :key="i" class="surface animate-pulse space-y-4 p-5">
      <div class="flex items-center gap-3">
        <div class="size-14 rounded-full bg-sand-200 dark:bg-sand-800" />
        <div class="flex-1 space-y-2">
          <div class="h-4 w-2/5 rounded bg-sand-200 dark:bg-sand-800" />
          <div class="h-3 w-3/5 rounded bg-sand-100 dark:bg-sand-800/70" />
        </div>
      </div>
      <div class="h-6 w-1/2 rounded bg-sand-200 dark:bg-sand-800" />
      <div class="flex gap-2">
        <div class="h-6 w-20 rounded-full bg-sand-100 dark:bg-sand-800/70" />
        <div class="h-6 w-24 rounded-full bg-sand-100 dark:bg-sand-800/70" />
      </div>
    </div>
  </div>
</template>
