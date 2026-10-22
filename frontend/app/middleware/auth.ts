export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore()
  const localePath = useLocalePath()
  const toLogin = () => navigateTo(localePath({ path: '/kirish', query: { redirect: to.fullPath } }))
  if (!auth.isLoggedIn) return toLogin()
  // Token bor, lekin foydalanuvchi yuklanmagan (masalan, tarmoq xatosi): bir marta qayta urinamiz
  if (!auth.user) {
    await auth.fetchMe()
    // 401 boʻlsa fetchMe tokenni tozalagan
    if (!auth.isLoggedIn) return toLogin()
    // Boshqa xato: kabinetni boʻsh foydalanuvchi bilan koʻrsatmaymiz, xato sahifasi chiqadi
    if (!auth.user) throw createError({ statusCode: 503, statusMessage: 'Service Unavailable', fatal: true })
  }
})
