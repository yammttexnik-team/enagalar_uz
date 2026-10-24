/** Sahifa render qilingan vaqt: serverdan brauzerga o'tadi, ilova ochilgach har daqiqada yangilanadi (plugins/now.client.ts). */
export function useRenderNow() {
  return useState('render-now', () => Date.now())
}
