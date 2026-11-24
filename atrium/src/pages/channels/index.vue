<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  usePickList,
  type CatalogList,
  type ShowroomLocale
} from '../../components/catalog/catalog'
import ChannelFields from '../../components/structure/ChannelFields.vue'
import {
  channelBody,
  channelForm,
  channelPath,
  loadTrees,
  newLabels,
  type ShowroomCategory,
  type StructureChannel
} from '../../components/structure/structure'

/**
 * `showroom.channels`: every channel (`channels.index`), a cursor page at a
 * time, with the locales card, ported from v1's `channels/index`. A locale is
 * added with its code and its label in the current locale (`locales.store`)
 * and deleted once confirmed (`locales.destroy`). A new channel is created
 * with its code, its label, its locales, currencies and category tree
 * (`channels.store`); the trees are the category roots.
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()
const localeAction = useCatalogAction()

const query = computed(() => {
  const cursor = queryValue(route.query.cursor)

  return cursor ? { cursor } : {}
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<StructureChannel>>(await showroom.GET('/channels', { params: { query: query.value } })), query)

const localeLoad = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomLocale>>(await showroom.GET('/locales', { params: { query: { per_page: ALL } } })))

const trees = usePickList<ShowroomCategory>(loadTrees)

const channels = computed(() => data.value?.data ?? [])
const locales = computed(() => localeLoad.data.value?.data ?? [])
const draft = reactive({ code: '', label: '' })
const fields = ref(channelForm())
const localeDraft = reactive({ code: '', label: '' })

const columns = computed<TableColumn<StructureChannel>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { accessorKey: 'locales', header: t('showroom.structure.channels.locales') },
  { accessorKey: 'currencies', header: t('showroom.structure.channels.currencies') },
  { accessorKey: 'category_tree', header: t('showroom.structure.channels.category_tree') }
])

async function create(): Promise<void> {
  const body = { code: draft.code.trim(), ...newLabels(locale.value, draft.label), ...channelBody(fields.value) }

  if (client && await action.run(async () => showroomData(await client.POST('/channels', { body: untyped(body) })))) {
    draft.code = ''
    draft.label = ''
    fields.value = channelForm()
    await reload()
  }
}

async function addLocale(): Promise<void> {
  const body = { code: localeDraft.code.trim(), ...newLabels(locale.value, localeDraft.label) }

  if (client && await localeAction.run(async () => showroomData(await client.POST('/locales', { body: untyped(body) })))) {
    localeDraft.code = ''
    localeDraft.label = ''
    await localeLoad.reload()
  }
}

async function deleteLocale(code: string): Promise<void> {
  if (!client || !window.confirm(t('showroom.structure.channels.confirm_delete_locale', { code }))) {
    return
  }

  if (await localeAction.run(async () => showroomData(await client.DELETE('/locales/{locale}', { params: { path: { locale: code } } })))) {
    await localeLoad.reload()
  }
}

/** A locale's label beside its code, or nothing when it has only its code. */
function localeLabel(entry: ShowroomLocale): string {
  const label = labelOf(entry.labels, locale.value, entry.code)

  return label === entry.code ? '' : label
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-channels">
    <AtriumPageHeader :title="t('showroom.structure.channels.title')" :description="t('showroom.structure.channels.description')" />

    <AtriumIntegrationScreen screen-key="showroom.channels">
      <div class="space-y-4">
        <UCard data-testid="showroom-locales">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.structure.channels.locales') }}</h2>
          </template>
          <CatalogState :ready="!localeLoad.pending.value && localeLoad.data.value !== null" :failed="localeLoad.failed.value" :status="localeLoad.status.value" compact>
            <div class="space-y-4">
              <AtriumState v-if="locales.length === 0" compact :title="t('showroom.structure.channels.no_locales')" data-testid="showroom-locales-empty" />
              <ul v-else class="flex flex-wrap gap-3">
                <li
                  v-for="entry in locales"
                  :key="entry.code"
                  class="flex items-center gap-2 rounded-md border border-default px-2 py-1 text-sm"
                  :data-testid="`showroom-locale-${entry.code}`"
                >
                  <span class="font-mono">{{ entry.code }}</span>
                  <span class="text-muted">{{ localeLabel(entry) }}</span>
                  <UButton
                    variant="ghost"
                    size="xs"
                    icon="i-lucide-trash-2"
                    :aria-label="t('showroom.catalog.delete')"
                    :loading="localeAction.busy.value"
                    :data-testid="`showroom-locale-delete-${entry.code}`"
                    @click="deleteLocale(entry.code)"
                  />
                </li>
              </ul>

              <form class="flex flex-wrap items-end gap-3" data-testid="showroom-locale-create-form" @submit.prevent="addLocale">
                <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.structure.channels.locale_code_hint')" :error="localeAction.error('code')" required>
                  <UInput v-model="localeDraft.code" required class="w-44" data-testid="showroom-locale-create-code" />
                </UFormField>
                <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="localeAction.error('labels')">
                  <UInput v-model="localeDraft.label" class="w-56" data-testid="showroom-locale-create-label" />
                </UFormField>
                <UButton type="submit" icon="i-lucide-plus" :loading="localeAction.busy.value" data-testid="showroom-locale-create-submit">{{ t('showroom.structure.channels.add_locale') }}</UButton>
              </form>
            </div>
          </CatalogState>
          <CatalogActionError :action="localeAction" class="mt-3" />
        </UCard>

        <UCard data-testid="showroom-channel-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.structure.channels.new') }}</h2>
          </template>
          <form class="flex flex-col gap-4" data-testid="showroom-channel-create-form" @submit.prevent="create">
            <div class="flex flex-wrap items-end gap-3">
              <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.structure.code_hint')" :error="action.error('code')" required>
                <UInput v-model="draft.code" required class="w-56" data-testid="showroom-channel-create-code" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="draft.label" class="w-56" data-testid="showroom-channel-create-label" />
              </UFormField>
            </div>
            <ChannelFields v-model="fields" :locales="locales" :trees="trees" :action="action" testid="showroom-channel-create" />
            <div>
              <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-channel-create-submit">{{ t('showroom.catalog.create') }}</UButton>
            </div>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <UTable :data="channels" :columns="columns" data-testid="showroom-channels-table">
              <template #empty>
                <AtriumState compact :title="t('showroom.structure.channels.empty')" data-testid="showroom-channels-empty" />
              </template>
              <template #code-cell="{ row: { original: channel } }">
                <span :data-testid="`showroom-channel-row-${channel.code}`">
                  <ULink :to="channelPath(channel.code)" class="font-mono font-medium hover:underline">{{ channel.code }}</ULink>
                </span>
              </template>
              <template #label-cell="{ row: { original: channel } }">{{ labelOf(channel.labels, locale, channel.code) }}</template>
              <template #locales-cell="{ row: { original: channel } }"><span class="font-mono text-xs">{{ (channel.locales ?? []).join(', ') }}</span></template>
              <template #currencies-cell="{ row: { original: channel } }"><span class="font-mono text-xs">{{ channel.currencies.join(', ') }}</span></template>
              <template #category_tree-cell="{ row: { original: channel } }"><span class="font-mono text-xs">{{ channel.category_tree ?? t('showroom.catalog.none') }}</span></template>
            </UTable>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-channels" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
