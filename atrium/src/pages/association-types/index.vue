<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui/components/Table.vue'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogList
} from '../../components/catalog/catalog'
import { newLabels, type StructureAssociationType } from '../../components/structure/structure'

/**
 * `showroom.association-types`: every association type
 * (`association-types.index`), a cursor page at a time, ported from v1's
 * `association-types/index`. A new type is created with its code, its label
 * in the current locale and its two switches (`association-types.store`);
 * those shape the associations stored with it, so they are never edited. A
 * type is deleted from its row once confirmed (`association-types.destroy`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()
const rowAction = useCatalogAction()

const query = computed(() => {
  const cursor = queryValue(route.query.cursor)

  return cursor ? { cursor } : {}
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<StructureAssociationType>>(await showroom.GET('/association-types', { params: { query: query.value } })), query)

const types = computed(() => data.value?.data ?? [])
const draft = reactive({ code: '', label: '', is_two_way: false, is_quantified: false })

const columns = computed<TableColumn<StructureAssociationType>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { id: 'switches', header: t('showroom.structure.attributes.switches') },
  { accessorKey: 'associations_count', header: t('showroom.structure.association_types.associations'), meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'actions', header: '' }
])

async function create(): Promise<void> {
  const body = { code: draft.code.trim(), ...newLabels(locale.value, draft.label), is_two_way: draft.is_two_way, is_quantified: draft.is_quantified }

  if (client && await action.run(async () => showroomData(await client.POST('/association-types', { body: untyped(body) })))) {
    draft.code = ''
    draft.label = ''
    draft.is_two_way = false
    draft.is_quantified = false
    await reload()
  }
}

async function destroy(code: string): Promise<void> {
  if (!client || !window.confirm(t('showroom.structure.association_types.confirm_delete', { code }))) {
    return
  }

  if (await rowAction.run(async () => showroomData(await client.DELETE('/association-types/{associationType}', { params: { path: { associationType: code } } })))) {
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-association-types">
    <AtriumPageHeader :title="t('showroom.structure.association_types.title')" :description="t('showroom.structure.association_types.description')" />

    <AtriumIntegrationScreen screen-key="showroom.association-types">
      <div class="space-y-4">
        <UCard data-testid="showroom-association-type-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.structure.association_types.new') }}</h2>
          </template>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-association-type-create-form" @submit.prevent="create">
            <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.structure.code_hint')" :error="action.error('code')" required>
              <UInput v-model="draft.code" required class="w-56" data-testid="showroom-association-type-create-code" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
              <UInput v-model="draft.label" class="w-56" data-testid="showroom-association-type-create-label" />
            </UFormField>
            <UCheckbox v-model="draft.is_two_way" :label="t('showroom.structure.association_types.two_way')" class="h-9 items-center" data-testid="showroom-association-type-create-two-way" />
            <UCheckbox v-model="draft.is_quantified" :label="t('showroom.structure.association_types.quantified')" class="h-9 items-center" data-testid="showroom-association-type-create-quantified" />
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-association-type-create-submit">{{ t('showroom.catalog.create') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <CatalogActionError :action="rowAction" />

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <UTable :data="types" :columns="columns" data-testid="showroom-association-types-table">
              <template #empty>
                <AtriumState compact :title="t('showroom.structure.association_types.empty')" data-testid="showroom-association-types-empty" />
              </template>
              <template #code-cell="{ row: { original: type } }">
                <span class="font-mono" :data-testid="`showroom-association-type-row-${type.code}`">{{ type.code }}</span>
              </template>
              <template #label-cell="{ row: { original: type } }">{{ labelOf(type.labels, locale, type.code) }}</template>
              <template #switches-cell="{ row: { original: type } }">
                <span class="flex flex-wrap gap-1">
                  <UBadge v-if="type.is_two_way" color="neutral" variant="outline">{{ t('showroom.structure.association_types.two_way') }}</UBadge>
                  <UBadge v-if="type.is_quantified" color="neutral" variant="outline">{{ t('showroom.structure.association_types.quantified') }}</UBadge>
                </span>
              </template>
              <template #associations_count-cell="{ row: { original: type } }"><span class="tabular-nums">{{ type.associations_count ?? t('showroom.catalog.none') }}</span></template>
              <template #actions-cell="{ row: { original: type } }">
                <UButton
                  variant="ghost"
                  size="sm"
                  icon="i-lucide-trash-2"
                  :aria-label="t('showroom.catalog.delete')"
                  :loading="rowAction.busy.value"
                  :data-testid="`showroom-association-type-delete-${type.code}`"
                  @click="destroy(type.code)"
                />
              </template>
            </UTable>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-association-types" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
