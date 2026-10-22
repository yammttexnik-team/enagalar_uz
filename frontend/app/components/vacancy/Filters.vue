<script setup lang="ts">
import type { Region } from '~/composables/useApi'

const props = defineProps<{ filters: Record<string, string>, regions: Region[] }>()
const emit = defineEmits<{ change: [patch: Record<string, string | boolean | null | undefined>] }>()
const { t } = useI18n()
const localizedName = useLocalizedName()
const { enumLabel, childAge } = useFormat()
const ALL = 'all'

const regionItems = computed(() => [{ label: t('search.allRegions'), value: ALL }, ...props.regions.map(r => ({ label: localizedName(r.name), value: r.slug }))])
const districts = computed(() => props.regions.find(r => r.slug === props.filters.region)?.districts ?? [])
const districtItems = computed(() => [{ label: t('search.allDistricts'), value: ALL }, ...districts.value.map(d => ({ label: localizedName(d.name), value: d.slug }))])
const languageItems = computed(() => [{ label: t('search.anyLanguage'), value: ALL }, ...LANGUAGES.map(l => ({ label: enumLabel('language', l), value: l }))])
const unitItems = computed(() => PRICE_UNITS.map(u => ({ label: enumLabel('priceUnit', u), value: u })))
const formatItems = computed(() => WORK_FORMATS.map(f => ({ label: enumLabel('workFormat', f), value: f })))
const ageItems = computed(() => [{ label: t('vacancy.anyAge'), value: ALL }, ...Array.from({ length: 11 }, (_, i) => ({ label: childAge(i), value: String(i) }))])

const val = (v: string | undefined) => (v && v !== ALL ? v : null)
const bool = (key: string) => props.filters[key] === '1'
function setSalary(e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/\D/g, '')
  emit('change', { salary_min: raw || null })
}
</script>

<template>
  <div class="space-y-6">
    <SearchField :label="t('search.region')">
      <USelect :model-value="filters.region || ALL" :items="regionItems" icon="i-lucide-map-pin" class="w-full" :aria-label="t('search.region')" @update:model-value="v => emit('change', { region: val(v as string), district: null })" />
      <USelect :model-value="filters.district || ALL" :items="districtItems" :disabled="!filters.region" class="w-full" :aria-label="t('search.district')" @update:model-value="v => emit('change', { district: val(v as string) })" />
    </SearchField>

    <SearchField :label="t('search.options')">
      <div class="space-y-3">
        <USwitch :model-value="bool('is_urgent')" :label="t('vacancy.urgentOnly')" @update:model-value="v => emit('change', { is_urgent: v ? '1' : null })" />
        <USwitch :model-value="bool('live_in')" :label="t('vacancy.liveIn')" @update:model-value="v => emit('change', { live_in: v ? '1' : null })" />
      </div>
    </SearchField>

    <SearchField :label="t('search.workFormat')">
      <SearchChips :label="t('search.workFormat')" :items="formatItems" :model-value="filters.work_format" @update:model-value="v => emit('change', { work_format: v })" />
    </SearchField>

    <SearchField :label="t('vacancy.childrenAge')">
      <USelect :model-value="filters.children_age || ALL" :items="ageItems" icon="i-lucide-baby" class="w-full" :aria-label="t('vacancy.childrenAge')" @update:model-value="v => emit('change', { children_age: val(v as string) })" />
    </SearchField>

    <SearchField :label="t('search.language')">
      <USelect :model-value="filters.language || ALL" :items="languageItems" icon="i-lucide-languages" class="w-full" :aria-label="t('search.language')" @update:model-value="v => emit('change', { language: val(v as string) })" />
    </SearchField>

    <SearchField :label="t('search.minSalary')">
      <div class="grid grid-cols-[1fr_auto] gap-2">
        <UInput :key="filters.salary_min || 'empty'" :model-value="filters.salary_min" inputmode="numeric" :placeholder="t('search.pricePlaceholder')" :aria-label="t('search.minSalary')" @change="setSalary" @keydown.enter="setSalary" />
        <USelect :model-value="filters.salary_unit || 'month'" :items="unitItems" class="w-32" :aria-label="t('search.unit')" @update:model-value="v => emit('change', { salary_unit: v === 'month' ? null : (v as string) })" />
      </div>
    </SearchField>
  </div>
</template>
