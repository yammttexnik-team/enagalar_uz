<template>
  <div class="min-h-screen flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-inverted">
      {{ $t("nav.skip") }}
    </a>
    <LayoutAppHeader />
    <main id="main" class="flex-1">
      <slot />
    </main>
    <LayoutAppFooter />
  </div>
</template>
