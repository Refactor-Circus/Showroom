<script setup lang="ts">
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
import { attributeGroupPath, newLabels, sortOrderField, type ShowroomAttributeGroup } from '../../components/structure/structure'

/**
 * `showroom.attribute-groups`: every attribute group
 * (`attribute-groups.index`), a cursor page at a time, ported from v1's
 * `attribute-groups/index`. A new group is created with its code, its label
 * in the current locale and its sort order (`attribute-groups.store`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const query = computed(() => {
  const cursor = queryValue(route.query.cursor)

  return cursor ? { cursor } : {}
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomAttributeGroup>>(await showroom.GET('/attribute-groups', { params: { query: query.value } })), query)

const groups = computed(() => data.value?.data ?? [])
const draft = reactive({ code: '', label: '', sort_order: '0' })

async function create(): Promise<void> {
  const body = { code: draft.code.trim(), ...newLabels(locale.value, draft.label), ...sortOrderField(draft.sort_order) }

  if (client && await action.run(async () => showroomData(await client.POST('/attribute-groups', { body: untyped(body) })))) {
    draft.code = ''
    draft.label = ''
    draft.sort_order = '0'
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-attribute-groups">
    <h1 class="text-xl font-semibold">{{ t('showroom.structure.groups.title') }}</h1>

    <AtriumIntegrationScreen screen-key="showroom.attribute-groups">
      <div class="space-y-4">
        <UCard data-testid="showroom-group-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.structure.groups.new') }}</h2>
          </template>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-group-create-form" @submit.prevent="create">
            <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.structure.code_hint')" :error="action.error('code')" required>
              <UInput v-model="draft.code" required class="w-56" data-testid="showroom-group-create-code" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
              <UInput v-model="draft.label" class="w-56" data-testid="showroom-group-create-label" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
              <UInput v-model="draft.sort_order" type="number" min="0" class="w-28" data-testid="showroom-group-create-sort" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-group-create-submit">{{ t('showroom.structure.groups.create') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <p v-if="groups.length === 0" class="atrium-empty" data-testid="showroom-attribute-groups-empty">{{ t('showroom.structure.groups.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-attribute-groups-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.structure.groups.attributes_count') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.catalog.fields.sort_order') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="group in groups" :key="group.code" :data-testid="`showroom-group-row-${group.code}`">
                  <td class="py-2 pr-3">
                    <RouterLink :to="attributeGroupPath(group.code)" class="font-mono font-medium hover:underline">{{ group.code }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3">{{ labelOf(group.labels, locale, group.code) }}</td>
                  <td class="py-2 pr-3 tabular-nums">{{ group.attributes_count ?? t('showroom.catalog.none') }}</td>
                  <td class="py-2 tabular-nums">{{ group.sort_order }}</td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-attribute-groups" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
