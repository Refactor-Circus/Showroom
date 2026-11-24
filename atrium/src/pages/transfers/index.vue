<script setup lang="ts">
import { integrationData } from '@refactor-circus/atrium'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import {
  ALL,
  labelOf,
  showroomData,
  untyped,
  useCatalogAction,
  usePickList,
  useShowroom,
  type CatalogList,
  type CatalogOne,
  type ShowroomChannel,
  type ShowroomFamily
} from '../../components/catalog/catalog'
import { loadTrees } from '../../components/structure/structure'
import TransferRunsTable from '../../components/transfers/TransferRunsTable.vue'
import {
  EXPORT_FORMATS,
  IMPORT_MODES,
  RECENT_RUNS,
  exportBody,
  flowPrefix,
  flowsUnder,
  newestRuns,
  type ExportForm,
  type ImportMode,
  type TransferRun
} from '../../components/transfers/transfers'
import type { AtriumExtensionData } from '@refactor-circus/atrium'

/**
 * `showroom.transfers`, ported from v1's `transfers/index` (D-035, D-042).
 * The recent runs come from Impex: the flows of `impex.flows.index` whose
 * slug starts with the screen's `options.flow_prefix`, one
 * `impex.runs.index?flow=` call per flow, merged into the 20 newest by
 * `created_at`. Without an active Impex a notice takes their place, and
 * import and export stay. An import (`showroom.imports.store`) sends the file
 * and mode as multipart; an export (`showroom.exports.store`) the format,
 * the family, category tree and channel picked, and published or working
 * copies. Either then reloads the runs.
 */
const { t, locale } = useI18n()
const client = useShowroom()
const impex = useAtriumIntegration('impex')
const importing = useCatalogAction()
const exporting = useCatalogAction()

// The same request `<AtriumIntegrationScreen>` makes, shared by its key.
const { data: screen, error: screenError } = useAtriumScreen('showroom.transfers')

const prefix = computed<string | null>(() => {
  if (screen.value) {
    return flowPrefix(screen.value.data.options as Record<string, unknown> | undefined)
  }

  return screenError.value ? flowPrefix(null) : null
})

const rowActions = computed<AtriumExtensionData[]>(() => screenError.value ? [] : screen.value?.data.sections['row.actions'] ?? [])

const runs = shallowRef<TransferRun[]>([])
const runsLoaded = ref(false)
const runsStatus = ref<number | null>(null)
const runsFailed = ref(false)

async function loadRuns(): Promise<void> {
  const impexClient = impex.client
  const under = prefix.value

  if (!impexClient || under === null) {
    return
  }

  try {
    const flows = integrationData(await impexClient.GET('/flows')).data
    const lists = await Promise.all(flowsUnder(flows, under).map(async flow =>
      integrationData(await impexClient.GET('/runs', { params: { query: { flow, per_page: RECENT_RUNS } } })).data as TransferRun[]))

    runs.value = newestRuns(lists)
    runsFailed.value = false
    runsStatus.value = null
  } catch (error) {
    runsFailed.value = true
    runsStatus.value = atriumStatus(error)
  } finally {
    runsLoaded.value = true
  }
}

watch(prefix, () => void loadRuns(), { immediate: true })

const families = usePickList(async showroom => showroomData<CatalogList<ShowroomFamily>>(await showroom.GET('/families', { params: { query: { per_page: ALL } } })))
const trees = usePickList(loadTrees)
const channels = usePickList(async showroom => showroomData<CatalogList<ShowroomChannel>>(await showroom.GET('/channels', { params: { query: { per_page: ALL } } })))

const mode = ref<ImportMode>('upsert')
const file = shallowRef<File | null>(null)
const fileInput = useTemplateRef<{ inputRef: HTMLInputElement | null }>('fileInput')

const modeItems = computed(() => IMPORT_MODES.map(entry => ({ label: t(`showroom.transfers.import.modes.${entry}`), value: entry })))
const formatItems = computed(() => EXPORT_FORMATS.map(entry => ({ label: t(`showroom.transfers.export.formats.${entry}`), value: entry })))
const familyItems = computed(() => [
  { label: t('showroom.transfers.export.all_families'), value: null },
  ...families.value.map(family => ({ label: labelOf(family.labels, locale.value, family.code), value: family.code }))
])
const treeItems = computed(() => [
  { label: t('showroom.transfers.export.all_categories'), value: null },
  ...trees.value.map(tree => ({ label: labelOf(tree.labels, locale.value, tree.code), value: tree.code }))
])
const channelItems = computed(() => [
  { label: t('showroom.transfers.export.no_channel'), value: null },
  ...channels.value.map(channel => ({ label: labelOf(channel.labels, locale.value, channel.code), value: channel.code }))
])
const exportForm = reactive<ExportForm>({ format: 'jsonl', family: '', category: '', scope: '', published: false })
const started = shallowRef<TransferRun | null>(null)

function choose(event: Event): void {
  file.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function startImport(): Promise<void> {
  const chosen = file.value

  if (!client || !chosen) {
    return
  }

  const form = new FormData()
  form.append('file', chosen)
  form.append('mode', mode.value)

  const answer: { run: TransferRun | null } = { run: null }

  if (await importing.run(async () => {
    answer.run = showroomData<CatalogOne<TransferRun>>(await client.POST('/imports', {
      body: form as never,
      bodySerializer: body => body as FormData
    })).data
  })) {
    started.value = answer.run
    file.value = null

    if (fileInput.value?.inputRef) {
      fileInput.value.inputRef.value = ''
    }

    await loadRuns()
  }
}

async function startExport(): Promise<void> {
  if (!client) {
    return
  }

  const answer: { run: TransferRun | null } = { run: null }

  if (await exporting.run(async () => {
    answer.run = showroomData<CatalogOne<TransferRun>>(await client.POST('/exports', { body: untyped(exportBody(exportForm)) })).data
  })) {
    started.value = answer.run
    await loadRuns()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-transfers">
    <AtriumPageHeader :title="t('showroom.transfers.title')" />

    <AtriumIntegrationScreen screen-key="showroom.transfers">
      <div class="space-y-4">
        <UAlert
          v-if="started"
          color="success"
          variant="subtle"
          icon="i-lucide-circle-check"
          :title="t('showroom.transfers.started_notice', { flow: started.flow })"
          data-testid="showroom-transfer-started"
        />

        <div class="grid gap-4 lg:grid-cols-2">
          <UCard data-testid="showroom-transfer-import">
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.transfers.import.title') }}</h2>
            </template>
            <form class="flex flex-col gap-3" data-testid="showroom-transfer-import-form" @submit.prevent="startImport">
              <CatalogActionError :action="importing" />
              <UFormField :label="t('showroom.transfers.import.file')" :hint="t('showroom.transfers.import.file_hint')" :error="importing.error('file')" required>
                <UInput ref="fileInput" type="file" accept=".csv,.jsonl,text/csv" class="w-full" data-testid="showroom-transfer-import-file" @change="choose" />
              </UFormField>
              <UFormField :label="t('showroom.transfers.import.mode')" :error="importing.error('mode')">
                <USelect v-model="mode" :items="modeItems" class="w-full" data-testid="showroom-transfer-import-mode" />
              </UFormField>
              <div>
                <UButton type="submit" icon="i-lucide-upload" :loading="importing.busy.value" data-testid="showroom-transfer-import-submit">{{ t('showroom.transfers.import.submit') }}</UButton>
              </div>
            </form>
          </UCard>

          <UCard data-testid="showroom-transfer-export">
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.transfers.export.title') }}</h2>
            </template>
            <form class="flex flex-col gap-3" data-testid="showroom-transfer-export-form" @submit.prevent="startExport">
              <CatalogActionError :action="exporting" />
              <UFormField :label="t('showroom.transfers.export.format')" :error="exporting.error('format')">
                <USelect v-model="exportForm.format" :items="formatItems" class="w-full" data-testid="showroom-transfer-export-format" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.family')" :error="exporting.error('family')">
                <USelect v-model="exportForm.family" :items="familyItems" :placeholder="t('showroom.transfers.export.all_families')" class="w-full" data-testid="showroom-transfer-export-family" />
              </UFormField>
              <UFormField :label="t('showroom.transfers.export.category_tree')" :error="exporting.error('category')">
                <USelect v-model="exportForm.category" :items="treeItems" :placeholder="t('showroom.transfers.export.all_categories')" class="w-full" data-testid="showroom-transfer-export-category" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.channel')" :error="exporting.error('scope')">
                <USelect v-model="exportForm.scope" :items="channelItems" :placeholder="t('showroom.transfers.export.no_channel')" class="w-full" data-testid="showroom-transfer-export-scope" />
              </UFormField>
              <UCheckbox v-model="exportForm.published" :label="t('showroom.transfers.export.published')" data-testid="showroom-transfer-export-published" />
              <div>
                <UButton type="submit" icon="i-lucide-download" :loading="exporting.busy.value" data-testid="showroom-transfer-export-submit">{{ t('showroom.transfers.export.submit') }}</UButton>
              </div>
            </form>
          </UCard>
        </div>

        <UCard data-testid="showroom-transfers-runs">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.transfers.recent') }}</h2>
          </template>
          <UAlert
            v-if="!impex.active"
            color="neutral"
            variant="subtle"
            icon="i-lucide-plug"
            :title="t('showroom.transfers.requires_impex')"
            :description="t('showroom.transfers.requires_impex_hint')"
            data-testid="showroom-transfers-requires-impex"
          />
          <AtriumNoAccess v-else-if="runsFailed && runsStatus === 403" compact />
          <AtriumErrorCard v-else-if="runsFailed" :message="t('showroom.transfers.runs_failed')" />
          <USkeleton v-else-if="!runsLoaded" class="h-24" />
          <AtriumState v-else-if="runs.length === 0" compact :title="t('showroom.transfers.no_runs')" data-testid="showroom-transfers-no-runs" />
          <TransferRunsTable v-else :runs="runs" :row-actions="rowActions" />
        </UCard>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
