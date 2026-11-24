<script setup lang="ts">
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
  type CatalogList
} from '../../components/catalog/catalog'
import { newLabels } from '../../components/structure/structure'
import OwnerTypeRules from '../../components/owners/OwnerTypeRules.vue'
import { ownerTypePath, ownerTypeRules, ownerTypeRulesBody, type ShowroomOwnerType } from '../../components/owners/owners'

/**
 * `showroom.owner-types`: every owner type (`owner-types.index`), a cursor
 * page at a time, ported from v1's `owner-types/index`, with the parent
 * types it allows and its owners counted. A new type is created
 * (`owner-types.store`) with its code, its label in the current locale and
 * its chain rules: any parent (`parent_types: null`) or only the ticked ones,
 * whether it may be a root and whether it owns products.
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const query = computed(() => {
  const cursor = queryValue(route.query.cursor)

  return cursor ? { cursor } : {}
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomOwnerType>>(await showroom.GET('/owner-types', { params: { query: untyped(query.value) } })), query)

const types = computed(() => data.value?.data ?? [])

// Every type, for the parent types a new one may sit under.
const all = shallowRef<ShowroomOwnerType[]>([])

async function loadAll(): Promise<void> {
  if (!client) {
    return
  }

  try {
    all.value = showroomData<CatalogList<ShowroomOwnerType>>(await client.GET('/owner-types', { params: { query: { per_page: ALL } } })).data
  } catch {
    all.value = []
  }
}

void loadAll()

const draft = reactive({ code: '', label: '' })
const rules = ref(ownerTypeRules())

async function create(): Promise<void> {
  if (!client) {
    return
  }

  const body = {
    code: draft.code.trim(),
    ...newLabels(locale.value, draft.label),
    ...ownerTypeRulesBody(rules.value, all.value.map(type => type.code))
  }

  if (await action.run(async () => showroomData(await client.POST('/owner-types', { body: untyped(body) })))) {
    draft.code = ''
    draft.label = ''
    rules.value = ownerTypeRules()
    await Promise.all([reload(), loadAll()])
  }
}

function parentsOf(type: ShowroomOwnerType): string {
  return type.parent_types === null || type.parent_types === undefined
    ? t('showroom.owner_types.any')
    : type.parent_types.length === 0 ? t('showroom.catalog.none') : type.parent_types.join(', ')
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-owner-types-page">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold">{{ t('showroom.owner_types.title') }}</h1>
        <p class="text-sm text-muted">{{ t('showroom.owner_types.description') }}</p>
      </div>
      <UButton to="/showroom/owners" variant="outline" icon="i-lucide-building-2">{{ t('showroom.owners.title') }}</UButton>
    </div>

    <AtriumIntegrationScreen screen-key="showroom.owner-types">
      <div class="space-y-4">
        <UCard data-testid="showroom-owner-type-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.owner_types.new') }}</h2>
          </template>
          <form class="flex flex-col gap-4" data-testid="showroom-owner-type-create-form" @submit.prevent="create">
            <CatalogActionError :action="action" />
            <div class="flex flex-wrap items-end gap-3">
              <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.owner_types.code_hint')" :error="action.error('code')" required>
                <UInput v-model="draft.code" required class="w-56" data-testid="showroom-owner-type-create-code" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="draft.label" class="w-56" data-testid="showroom-owner-type-create-label" />
              </UFormField>
            </div>
            <OwnerTypeRules v-model="rules" :types="all" :action="action" testid="showroom-owner-type-create" />
            <div>
              <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-owner-type-create-submit">{{ t('showroom.catalog.create') }}</UButton>
            </div>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <p v-if="types.length === 0" class="atrium-empty" data-testid="showroom-owner-types-empty">{{ t('showroom.owner_types.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-owner-types-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.owner_types.parent_types') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.owner_types.can_be_root') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.owner_types.owns_products') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.owner_types.owners') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="type in types" :key="type.code" :data-testid="`showroom-owner-type-row-${type.code}`">
                  <td class="py-2 pr-3">
                    <RouterLink :to="ownerTypePath(type.code)" class="font-mono font-medium hover:underline">{{ type.code }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3">{{ labelOf(type.labels, locale, type.code) }}</td>
                  <td class="py-2 pr-3 font-mono text-xs">{{ parentsOf(type) }}</td>
                  <td class="py-2 pr-3">{{ type.can_be_root ? t('showroom.catalog.yes') : t('showroom.catalog.no') }}</td>
                  <td class="py-2 pr-3">{{ type.owns_products ? t('showroom.catalog.yes') : t('showroom.catalog.no') }}</td>
                  <td class="py-2 tabular-nums">{{ type.owners_count ?? 0 }}</td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-owner-types" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
