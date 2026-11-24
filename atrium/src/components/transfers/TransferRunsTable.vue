<script setup lang="ts">
import type { AtriumExtensionData } from '@refactor-circus/atrium'
import type { TableColumn } from '@nuxt/ui/components/Table.vue'
import { formatDate } from '../catalog/catalog'
import { runError, runStatusColor, type TransferRun } from './transfers'

/**
 * The recent transfer runs (v1's `transfers/index` table): each run's flow,
 * status, a failed run's message and when it started and finished, with the
 * sections packages place in the screen's `row.actions` slot, given the run
 * id as their record.
 */
const props = defineProps<{ runs: TransferRun[], rowActions: AtriumExtensionData[] }>()

const { t } = useI18n()

const columns = computed<TableColumn<TransferRun>[]>(() => [
  { accessorKey: 'flow', header: t('showroom.transfers.flow') },
  { accessorKey: 'status', header: t('showroom.transfers.status') },
  { accessorKey: 'started_at', header: t('showroom.transfers.started') },
  { accessorKey: 'finished_at', header: t('showroom.transfers.finished') },
  ...(props.rowActions.length > 0 ? [{ id: 'actions', header: '', meta: { class: { td: 'text-right' } } }] : [])
])
</script>

<template>
  <UTable :data="props.runs" :columns="columns" data-testid="showroom-transfers-table">
    <template #flow-cell="{ row: { original: run } }">
      <span class="font-mono text-xs" :data-testid="`showroom-transfer-run-${run.id}`">{{ run.flow }}</span>
    </template>
    <template #status-cell="{ row: { original: run } }">
      <UBadge :color="runStatusColor(run.status)" variant="subtle">{{ run.status }}</UBadge>
      <p v-if="runError(run)" class="mt-1 text-xs text-error">{{ runError(run) }}</p>
    </template>
    <template #started_at-cell="{ row: { original: run } }">
      <span class="text-muted">{{ formatDate(run.started_at ?? run.created_at) ?? t('showroom.catalog.none') }}</span>
    </template>
    <template #finished_at-cell="{ row: { original: run } }">
      <span class="text-muted">{{ formatDate(run.finished_at) ?? t('showroom.catalog.none') }}</span>
    </template>
    <template #actions-cell="{ row: { original: run } }">
      <div class="flex justify-end gap-2">
        <AtriumExtension v-for="section in props.rowActions" :key="section.key" :extension="section" :record="run.id" />
      </div>
    </template>
  </UTable>
</template>
