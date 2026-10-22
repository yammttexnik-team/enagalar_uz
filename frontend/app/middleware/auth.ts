export default defineNuxtRouteMiddleware(() => {
  const auth = useAuthStore()
  const localePath = useLocalePath()
  if (!auth.isLoggedIn) return navigateTo(localePath('/kirish'))
})
