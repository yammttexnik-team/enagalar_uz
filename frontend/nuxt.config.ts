// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: ['@nuxt/ui', '@nuxtjs/i18n', '@pinia/nuxt'],

  // Ishlatilgan ikonkalar build ichiga kiradi (runtime'da tarmoqdan yuklanmaydi)
  icon: {
    clientBundle: { scan: true },
  },

  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    // Faqat SSR uchun ichki API manzili (masalan, http://caddy/api/v1). Bo'sh bo'lsa public.apiBase ishlatiladi.
    apiServer: '',
    public: {
      apiBase: 'http://localhost:8000/api/v1',
      siteName: 'enagalar.uz',
      siteUrl: 'https://enagalar.uz',
      // Laravel Reverb (Pusher protokoli). NUXT_PUBLIC_REVERB_* bilan almashtiriladi (deploy/docker-compose.prod.yml).
      reverbAppKey: 'enagalar-local-key',
      reverbHost: 'localhost',
      reverbPort: 8080,
      reverbScheme: 'http',
    },
  },

  fonts: {
    families: [
      { name: 'Mulish', provider: 'google', weights: [800, 900], styles: ['normal'], subsets: ['latin', 'latin-ext', 'cyrillic', 'cyrillic-ext'] },
      { name: 'Inter', provider: 'google', weights: [400, 500, 600], styles: ['normal'], subsets: ['latin', 'latin-ext', 'cyrillic', 'cyrillic-ext'] },
    ],
  },

  app: {
    head: {
      title: 'enagalar.uz',
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/brand/favicon.svg' },
        { rel: 'icon', href: '/favicon.ico', sizes: '48x48' },
        { rel: 'apple-touch-icon', href: '/brand/apple-touch-icon.png' },
      ],
      meta: [
        { name: 'theme-color', content: '#018186' },
        { property: 'og:image', content: '/brand/og-image.png' },
        { property: 'og:type', content: 'website' },
        { property: 'og:site_name', content: 'enagalar.uz' },
        { name: 'twitter:card', content: 'summary_large_image' },
      ],
    },
  },

  i18n: {
    baseUrl: process.env.NUXT_PUBLIC_SITE_URL || 'https://enagalar.uz',
    defaultLocale: 'uz',
    strategy: 'prefix_except_default',
    locales: [
      { code: 'uz', language: 'uz-Latn', name: "O'zbekcha", file: 'uz.json' },
      { code: 'ru', language: 'ru-RU', name: 'Русский', file: 'ru.json' },
    ],
  },
})
