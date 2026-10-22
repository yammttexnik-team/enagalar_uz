// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: ['@nuxt/ui', '@nuxtjs/i18n', '@pinia/nuxt'],

  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api/v1',
      siteName: 'enagalar.uz',
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
      htmlAttrs: { lang: 'uz' },
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
    defaultLocale: 'uz',
    strategy: 'prefix_except_default',
    locales: [
      { code: 'uz', language: 'uz-Latn', name: "O'zbekcha", file: 'uz.json' },
      { code: 'ru', language: 'ru-RU', name: 'Русский', file: 'ru.json' },
    ],
  },
})
