export interface BonusTransaction {
  id: number
  amount: number
  type: 'survey' | 'listing' | 'referral' | 'manual' | 'spend' | 'expire' | 'revoke'
  description: string | null
  expires_at: string | null
  created_at: string
}

export interface BonusWallet {
  balance: number
  referral_code: string
  referral_link: string
  transactions: { data: BonusTransaction[], meta: { total: number, current_page: number, last_page: number } }
}

export interface SurveyListItem { slug: string, audience: 'nanny' | 'parent', title: string, bonus: number, completed: boolean }

/** Bonus balansi (kabinet sarlavhasi va umumiy sahifa uchun umumiy holat). */
export function useBonus() {
  const api = useApi()
  const balance = useState<number | null>('bonus-balance', () => null)

  async function refreshBalance() {
    try {
      balance.value = (await api<BonusWallet>('/me/bonus', { query: { page: 1 } })).balance
    }
    catch { /* chip koʻrinmaydi, xalaqit bermaydi */ }
  }

  return { balance, refreshBalance }
}
