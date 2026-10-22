export default defineAppConfig({
  ui: {
    colors: {
      primary: 'brand',
      secondary: 'apricot',
      neutral: 'sand',
      success: 'leaf',
      warning: 'honey',
      error: 'pomegranate',
      info: 'azure',
    },
    button: {
      slots: { base: 'font-semibold rounded-xl' },
    },
    input: { slots: { base: 'rounded-xl' } },
    select: { slots: { base: 'rounded-xl' } },
    selectMenu: { slots: { base: 'rounded-xl' } },
    card: { slots: { root: 'rounded-3xl' } },
    badge: { slots: { base: 'rounded-full font-semibold' } },
    accordion: { slots: { trigger: 'text-base font-semibold py-4', label: 'text-start' } },
  },
})
