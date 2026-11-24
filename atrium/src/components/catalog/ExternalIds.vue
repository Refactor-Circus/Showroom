<script setup lang="ts">
import { formatDate, type ExternalIdsForm } from './catalog'

/**
 * A record's ids in other sources, edited as a map (v1's
 * `partials/external-ids`): an input per source it holds (emptied removes
 * it) and one new source and id to add. When each source last synced its id
 * is shown read-only; Showroom stamps it.
 */
const props = defineProps<{ syncedAt: Record<string, string | null>, disabled?: boolean, error: (field: string, exact?: boolean) => string | undefined }>()

const form = defineModel<ExternalIdsForm>({ required: true })

const { t } = useI18n()
</script>

<template>
  <div data-testid="showroom-external-ids">
    <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.catalog.external_ids.title') }}</p>
    <div class="flex flex-col gap-3">
      <div v-for="source in Object.keys(form.ids)" :key="source" class="flex flex-wrap items-center gap-3" :data-external-source="source">
        <span class="w-44 font-mono text-xs">{{ source }}</span>
        <UInput v-model="form.ids[source]" :aria-label="source" :disabled="props.disabled" class="w-56" :data-testid="`showroom-external-id-${source}`" />
        <span class="text-xs text-muted" :data-testid="`showroom-external-synced-${source}`">
          {{ props.syncedAt[source] ? t('showroom.catalog.external_ids.synced', { time: formatDate(props.syncedAt[source]) }) : t('showroom.catalog.external_ids.never_synced') }}
        </span>
        <small v-if="props.error(`external_ids.${source}`, true)" class="text-xs text-error">{{ props.error(`external_ids.${source}`, true) }}</small>
      </div>

      <div class="flex flex-wrap items-start gap-3">
        <UFormField :label="t('showroom.catalog.external_ids.source')" :hint="t('showroom.catalog.external_ids.source_hint')" :error="form.source ? props.error(`external_ids.${form.source}`) : undefined">
          <UInput v-model="form.source" :disabled="props.disabled" class="w-44" data-testid="showroom-external-new-source" />
        </UFormField>
        <UFormField :label="t('showroom.catalog.external_ids.id')">
          <UInput v-model="form.id" :disabled="props.disabled" class="w-56" data-testid="showroom-external-new-id" />
        </UFormField>
      </div>
      <small v-if="props.error('external_ids', true)" class="text-xs text-error">{{ props.error('external_ids', true) }}</small>
    </div>
  </div>
</template>
