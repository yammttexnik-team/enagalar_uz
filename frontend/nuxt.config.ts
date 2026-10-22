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

  app: {
    head: {
      htmlAttrs: { lang: 'uz' },
      title: 'enagalar.uz',
      meta: [
        { name: 'description', content: "Ishonchli enagalar va ota-onalar uchun platforma" },
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
