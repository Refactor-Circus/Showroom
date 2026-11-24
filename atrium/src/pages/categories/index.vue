<script setup lang="ts">
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
    <div>
      <h1 class="text-xl font-semibold">{{ t('showroom.structure.categories.title') }}</h1>
      <p class="text-sm text-muted">{{ t('showroom.structure.categories.description') }}</p>
    </div>

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
            <p v-if="trees.length === 0" class="atrium-empty" data-testid="showroom-categories-empty">{{ t('showroom.structure.categories.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-categories-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.structure.categories.children') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="tree in trees" :key="tree.code" :data-testid="`showroom-category-row-${tree.code}`">
                  <td class="py-2 pr-3">
                    <RouterLink :to="categoryPath(tree.code)" class="font-mono font-medium hover:underline">{{ tree.code }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3">{{ labelOf(tree.labels, locale, tree.code) }}</td>
                  <td class="py-2 tabular-nums">{{ tree.children_count ?? t('showroom.catalog.none') }}</td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-categories" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
