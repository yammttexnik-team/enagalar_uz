<script setup lang="ts">
import type { Region } from '~/composables/useApi'
import type { Skill } from '~/composables/useCatalog'

const props = defineProps<{ filters: Record<string, string>, regions: Region[], skills: Skill[] }>()
const emit = defineEmits<{ change: [patch: Record<string, string | boolean | null | undefined>] }>()
const { t } = useI18n()
const localizedName = useLocalizedName()
const { enumLabel } = useFormat()
const ALL = 'all'

const regionItems = computed(() => [
  { label: t('search.allRegions'), value: ALL },
  ...props.regions.map(r => ({ label: localizedName(r.name), value: r.slug })),
])
const districts = computed(() => props.regions.find(r => r.slug === props.filters.region)?.districts ?? [])
const districtItems = computed(() => [
  { label: t('search.allDistricts'), value: ALL },
  ...districts.value.map(d => ({ label: localizedName(d.name), value: d.slug })),
])
const languageItems = computed(() => [
  { label: t('search.anyLanguage'), value: ALL },
  ...LANGUAGES.map(l => ({ label: enumLabel('language', l), value: l })),
])
const unitItems = computed(() => PRICE_UNITS.map(u => ({ label: enumLabel('priceUnit', u), value: u })))
const experienceItems = computed(() => [
  { label: t('search.experienceAny'), value: ALL },
  ...[1, 3, 5, 10].map(n => ({ label: t('search.experienceOption', { n }), value: String(n) })),
])
const formatItems = computed(() => WORK_FORMATS.map(f => ({ label: enumLabel('workFormat', f), value: f })))
const ageItems = computed(() => AGE_GROUPS.map(a => ({ label: enumLabel('ageGroup', a), value: a })))
const skillItems = computed(() => props.skills.map(s => ({ label: localizedName(s.name), value: s.slug })))

const val = (v: string | undefined) => (v && v !== ALL ? v : null)
const bool = (key: string) => props.filters[key] === '1'

function setPrice(e: Event) {
  const raw = (e.target as HTMLInputElement).value.replace(/\D/g, '')
  emit('change', { price_max: raw ? raw : null })
}
</script>

<template>
  <div class="space-y-6">
    <SearchField :label="t('search.region')">
      <USelect
        :model-value="filters.region || ALL"
        :items="regionItems"
        icon="i-lucide-map-pin"
        class="w-full"
        :aria-label="t('search.region')"
        @update:model-value="v => emit('change', { region: val(v as string), district: null })"
      />
      <USelect
        :model-value="filters.district || ALL"
        :items="districtItems"
        :disabled="!filters.region"
        :placeholder="t('search.pickRegionFirst')"
        class="w-full"
        :aria-label="t('search.district')"
        @update:model-value="v => emit('change', { district: val(v as string) })"
      />
    </SearchField>

    <SearchField :label="t('search.options')">
      <div class="space-y-3">
        <USwitch :model-value="bool('verified_only')" :label="t('nanny.verifiedOnly')" @update:model-value="v => emit('change', { verified_only: v ? '1' : null })" />
        <USwitch :model-value="bool('available_only')" :label="t('nanny.availableOnly')" @update:model-value="v => emit('change', { available_only: v ? '1' : null })" />
        <USwitch :model-value="bool('live_in')" :label="t('nanny.liveIn')" @update:model-value="v => emit('change', { live_in: v ? '1' : null })" />
      </div>
    </SearchField>

    <SearchField :label="t('search.workFormat')">
      <SearchChips :label="t('search.workFormat')" :items="formatItems" :model-value="filters.work_format" @update:model-value="v => emit('change', { work_format: v })" />
    </SearchField>

    <SearchField :label="t('search.ageGroup')">
      <SearchChips :label="t('search.ageGroup')" :items="ageItems" :model-value="filters.age_group" @update:model-value="v => emit('change', { age_group: v })" />
    </SearchField>

    <SearchField :label="t('search.language')">
      <USelect
        :model-value="filters.language || ALL"
        :items="languageItems"
        icon="i-lucide-languages"
        class="w-full"
        :aria-label="t('search.language')"
        @update:model-value="v => emit('change', { language: val(v as string) })"
      />
    </SearchField>

    <SearchField :label="t('search.maxPrice')">
      <div class="grid grid-cols-[1fr_auto] gap-2">
        <UInput
          :key="filters.price_max || 'empty'"
          :model-value="filters.price_max"
          inputmode="numeric"
          :placeholder="t('search.pricePlaceholder')"
          :aria-label="t('search.maxPrice')"
          @change="setPrice"
          @keydown.enter="setPrice"
        />
        <USelect
          :model-value="filters.price_unit || 'month'"
          :items="unitItems"
          class="w-32"
          :aria-label="t('search.unit')"
          @update:model-value="v => emit('change', { price_unit: v === 'month' ? null : (v as string) }, )"
        />
      </div>
    </SearchField>

    <SearchField :label="t('search.experience')">
      <USelect
        :model-value="filters.experience_min || ALL"
        :items="experienceItems"
        icon="i-lucide-briefcase"
        class="w-full"
        :aria-label="t('search.experience')"
        @update:model-value="v => emit('change', { experience_min: val(v as string) })"
      />
    </SearchField>

    <SearchField v-if="skillItems.length" :label="t('search.skills')">
      <SearchChips :label="t('search.skills')" :items="skillItems" :model-value="filters.skill" @update:model-value="v => emit('change', { skill: v })" />
    </SearchField>
  </div>
</template>
