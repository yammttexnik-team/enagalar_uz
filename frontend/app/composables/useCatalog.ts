import type { Region } from '~/composables/useApi'

export interface Skill {
  id: number
  slug: string
  name: LocalizedName
}

/** Viloyatlar va tumanlar (bosh sahifa bilan bir xil kesh kaliti). */
export function useRegions() {
  const api = useApi()
  return useAsyncData('regions', () => api<{ data: Region[] }>('/regions').then(r => r.data), { default: () => [] as Region[] })
}

export function useSkills() {
  const api = useApi()
  return useAsyncData('skills', () => api<{ data: Skill[] }>('/skills').then(r => r.data), { default: () => [] as Skill[] })
}
