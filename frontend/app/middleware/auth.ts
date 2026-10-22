export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore()
  const localePath = useLocalePath()
  if (!auth.isLoggedIn) return navigateTo(localePath({ path: '/kirish', query: { redirect: to.fullPath } }))
})
