/** Saqlangan kampaniya va taklif kodlari (cookie, 30 kun). */
export function useAttribution() {
  const campaign = useCookie<string | null>('enagalar_c', { default: () => null })
  const referral = useCookie<string | null>('enagalar_r', { default: () => null })
  return { campaign, referral }
}
