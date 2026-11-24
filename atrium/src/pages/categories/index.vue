<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  categoryPath,
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogList
} from '../../components/catalog/catalog'
import { newLabels, type ShowroomCategory } from '../../components/structure/structure'

/**
 * `showroom.categories`: the category trees, each root category a tree
 * (`categories.index?roots=1`), a cursor page at a time, ported from v1's
 * `categories/index`. A new tree is created with its code and its label in
 * the current locale (`categories.store`, no parent). Each tree's branch is
 * drawn on its own page.
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const query = computed(() => {
  const cursor = queryValue(route.query.cursor)

  return { roots: 1, ...(cursor ? { cursor } : {}) }
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomCategory>>(await showroom.GET('/categories', { params: { query: untyped(query.value) } })), query)

const trees = computed(() => data.value?.data ?? [])
const draft = reactive({ code: '', label: '' })

const columns = computed<TableColumn<ShowroomCategory>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { accessorKey: 'children_count', header: t('showroom.structure.categories.children'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

async function create(): Promise<void> {
  const body = { code: draft.code.trim(), ...newLabels(locale.value, draft.label) }

  if (client && await action.run(async () => showroomData(await client.POST('/categories', { body: untyped(body) })))) {
    draft.code = ''
    draft.label = ''
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-categories">
    <AtriumPageHeader :title="t('showroom.structure.categories.title')" :description="t('showroom.structure.categories.description')" />

    <AtriumIntegrationScreen screen-key="showroom.categories">
      <div class="space-y-4">
        <UCard data-testid="showroom-tree-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.structure.categories.new_tree') }}</h2>
          </template>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-tree-create-form" @submit.prevent="create">
            <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.structure.code_hint')" :error="action.error('code')" required>
              <UInput v-model="draft.code" required class="w-56" data-testid="showroom-tree-create-code" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
              <UInput v-model="draft.label" class="w-56" data-testid="showroom-tree-create-label" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-tree-create-submit">{{ t('showroom.catalog.create') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <UTable :data="trees" :columns="columns" data-testid="showroom-categories-table">
              <template #empty>
                <AtriumState compact :title="t('showroom.structure.categories.empty')" data-testid="showroom-categories-empty" />
              </template>
              <template #code-cell="{ row: { original: tree } }">
                <span :data-testid="`showroom-category-row-${tree.code}`">
                  <ULink :to="categoryPath(tree.code)" class="font-mono font-medium hover:underline">{{ tree.code }}</ULink>
                </span>
              </template>
              <template #label-cell="{ row: { original: tree } }">{{ labelOf(tree.labels, locale, tree.code) }}</template>
              <template #children_count-cell="{ row: { original: tree } }"><span class="tabular-nums">{{ tree.children_count ?? t('showroom.catalog.none') }}</span></template>
            </UTable>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-categories" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
