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
    <div>
      <h1 class="text-xl font-semibold">{{ t('showroom.structure.association_types.title') }}</h1>
      <p class="text-sm text-muted">{{ t('showroom.structure.association_types.description') }}</p>
    </div>

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
            <label class="flex h-9 items-center gap-2 text-sm">
              <input v-model="draft.is_two_way" type="checkbox" data-testid="showroom-association-type-create-two-way">
              {{ t('showroom.structure.association_types.two_way') }}
            </label>
            <label class="flex h-9 items-center gap-2 text-sm">
              <input v-model="draft.is_quantified" type="checkbox" data-testid="showroom-association-type-create-quantified">
              {{ t('showroom.structure.association_types.quantified') }}
            </label>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-association-type-create-submit">{{ t('showroom.catalog.create') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <CatalogActionError :action="rowAction" />

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <p v-if="types.length === 0" class="atrium-empty" data-testid="showroom-association-types-empty">{{ t('showroom.structure.association_types.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-association-types-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.structure.attributes.switches') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.structure.association_types.associations') }}</th>
                  <th class="py-2 font-medium" />
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="type in types" :key="type.code" :data-testid="`showroom-association-type-row-${type.code}`">
                  <td class="py-2 pr-3 font-mono">{{ type.code }}</td>
                  <td class="py-2 pr-3">{{ labelOf(type.labels, locale, type.code) }}</td>
                  <td class="py-2 pr-3">
                    <span class="flex flex-wrap gap-1">
                      <UBadge v-if="type.is_two_way" color="neutral" variant="outline">{{ t('showroom.structure.association_types.two_way') }}</UBadge>
                      <UBadge v-if="type.is_quantified" color="neutral" variant="outline">{{ t('showroom.structure.association_types.quantified') }}</UBadge>
                    </span>
                  </td>
                  <td class="py-2 pr-3 tabular-nums">{{ type.associations_count ?? t('showroom.catalog.none') }}</td>
                  <td class="py-2">
                    <UButton
                      variant="ghost"
                      size="sm"
                      icon="i-lucide-trash-2"
                      :aria-label="t('showroom.catalog.delete')"
                      :loading="rowAction.busy.value"
                      :data-testid="`showroom-association-type-delete-${type.code}`"
                      @click="destroy(type.code)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-association-types" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
