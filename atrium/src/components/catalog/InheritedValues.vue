<script setup lang="ts">
import type { CatalogValues } from './catalog'

/**
 * The values a record inherits from its product models, read-only (v1's
 * `partials/inherited`): one row per slot, named by code, locale and channel.
 */
const props = defineProps<{ values: CatalogValues }>()

const { t } = useI18n()

const rows = computed(() => Object.entries(props.values).flatMap(([code, slots]) => slots.map(slot => ({
  key: [code, slot.locale, slot.scope].join('|'),
  term: [code, slot.locale, slot.scope].filter(part => part).join(' · '),
  data: slot.data
}))))

function shown(data: unknown): string {
  if (typeof data === 'boolean') {
    return data ? t('showroom.catalog.yes') : t('showroom.catalog.no')
  }

  if (data === null || data === undefined) {
    return t('showroom.catalog.none')
  }

  return typeof data === 'object' ? JSON.stringify(data) : String(data)
}
</script>

<template>
  <UCard v-if="rows.length > 0" data-testid="showroom-inherited-values">
    <template #header>
      <h2 class="font-semibold">{{ t('showroom.catalog.values.inherited') }}</h2>
    </template>
    <dl class="grid gap-x-4 gap-y-2 text-sm sm:grid-cols-[auto_1fr]">
      <template v-for="row in rows" :key="row.key">
        <dt class="font-mono text-xs text-muted">{{ row.term }}</dt>
        <dd :data-testid="`showroom-inherited-${row.key}`">{{ shown(row.data) }}</dd>
      </template>
    </dl>
  </UCard>
</template>
