/** Hydration tugagach "hozir"ni haqiqiy vaqtga o'tkazadi va har daqiqada yangilaydi ("5 daqiqa oldin" kabi matnlar uchun). */
export default defineNuxtPlugin((nuxtApp) => {
  const now = useRenderNow()
  nuxtApp.hook('app:suspense:resolve', () => {
    now.value = Date.now()
    setInterval(() => { now.value = Date.now() }, 60_000)
  })
})
