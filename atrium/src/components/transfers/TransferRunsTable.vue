<script setup lang="ts">
import type { AtriumExtensionData } from '@refactor-circus/atrium'
import { formatDate } from '../catalog/catalog'
import { runError, runStatusColor, type TransferRun } from './transfers'

/**
 * The recent transfer runs (v1's `transfers/index` table): each run's flow,
 * status, a failed run's message and when it started and finished, with the
 * sections packages place in the screen's `row.actions` slot, given the run
 * id as their record.
 */
defineProps<{ runs: TransferRun[], rowActions: AtriumExtensionData[] }>()

const { t } = useI18n()
</script>

<template>
  <table class="w-full text-left text-sm" data-testid="showroom-transfers-table">
    <thead class="text-muted">
      <tr>
        <th class="py-2 pr-3 font-medium">{{ t('showroom.transfers.flow') }}</th>
        <th class="py-2 pr-3 font-medium">{{ t('showroom.transfers.status') }}</th>
        <th class="py-2 pr-3 font-medium">{{ t('showroom.transfers.started') }}</th>
        <th class="py-2 pr-3 font-medium">{{ t('showroom.transfers.finished') }}</th>
        <th v-if="rowActions.length > 0" class="py-2" />
      </tr>
    </thead>
    <tbody class="divide-y divide-default">
      <tr v-for="run in runs" :key="run.id" :data-testid="`showroom-transfer-run-${run.id}`">
        <td class="py-2 pr-3 font-mono text-xs">{{ run.flow }}</td>
        <td class="py-2 pr-3">
          <UBadge :color="runStatusColor(run.status)" variant="subtle">{{ run.status }}</UBadge>
          <p v-if="runError(run)" class="mt-1 text-xs text-error">{{ runError(run) }}</p>
        </td>
        <td class="py-2 pr-3 text-muted">{{ formatDate(run.started_at ?? run.created_at) ?? t('showroom.catalog.none') }}</td>
        <td class="py-2 pr-3 text-muted">{{ formatDate(run.finished_at) ?? t('showroom.catalog.none') }}</td>
        <td v-if="rowActions.length > 0" class="py-2 text-right">
          <div class="flex justify-end gap-2">
            <AtriumExtension v-for="section in rowActions" :key="section.key" :extension="section" :record="run.id" />
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</template>
