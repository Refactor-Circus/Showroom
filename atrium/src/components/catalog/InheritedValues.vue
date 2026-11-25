<script setup lang="ts">
import type { CatalogValues } from './catalog'

/**
 * The values a record inherits from its product models, read-only (v1's
 * `partials/inherited`): one row per slot, named by code, locale and channel.
 */
const props = defineProps<{ values: CatalogValues }>()

const { t } = useI18n()

function shown(data: unknown): string {
  if (typeof data === 'boolean') {
    return data ? t('showroom.catalog.yes') : t('showroom.catalog.no')
  }

  if (data === null || data === undefined) {
    return t('showroom.catalog.none')
  }

  return typeof data === 'object' ? JSON.stringify(data) : String(data)
}

const items = computed(() => Object.entries(props.values).flatMap(([code, slots]) => slots.map((slot) => {
  const key = [code, slot.locale, slot.scope].join('|')

  return { key, label: [code, slot.locale, slot.scope].filter(part => part).join(' · '), value: shown(slot.data), testid: `showroom-inherited-${key}` }
})))
</script>

<template>
  <UCard v-if="items.length > 0" data-testid="showroom-inherited-values">
    <template #header>
      <h2 class="font-semibold">{{ t('showroom.catalog.values.inherited') }}</h2>
    </template>
    <AtriumDetails :items="items" />
  </UCard>
</template>
