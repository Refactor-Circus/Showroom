<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  blankToNull,
  familyPath,
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogList,
  type ShowroomFamily
} from '../../components/catalog/catalog'

/**
 * `showroom.families`: every family (`families.index`), ported from v1's
 * `families/index`. The search lives in the URL and goes to the endpoint as
 * its query, a cursor page at a time. A new family is created with its code,
 * its label in the current locale and its sort order (`families.store`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useCatalogAction()

const query = computed(() => {
  const search = queryValue(route.query.search)
  const cursor = queryValue(route.query.cursor)

  return { ...(search ? { search } : {}), ...(cursor ? { cursor } : {}) }
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomFamily>>(await showroom.GET('/families', { params: { query: query.value } })), query)

const families = computed(() => data.value?.data ?? [])

const columns = computed<TableColumn<ShowroomFamily>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { accessorKey: 'attributes_count', header: t('showroom.catalog.families.attributes_count'), meta: { class: { th: 'text-right', td: 'text-right tabular-nums' } } },
  { accessorKey: 'label_attribute', header: t('showroom.catalog.families.label_attribute') }
])

const search = ref('')
const draft = reactive({ code: '', label: '', sort_order: '0' })

watch(() => route.query.search, () => {
  search.value = queryValue(route.query.search) ?? ''
}, { immediate: true })

function filter(): void {
  router.replace({ query: { ...route.query, search: search.value || undefined, cursor: undefined } })
}

function clear(): void {
  router.replace({ query: {} })
}

async function create(): Promise<void> {
  const label = blankToNull(draft.label)

  if (client && await action.run(async () => showroomData(await client.POST('/families', {
    body: untyped({
      code: draft.code.trim(),
      ...(label ? { labels: { [locale.value]: label } } : {}),
      sort_order: Number(draft.sort_order) || 0
    })
  })))) {
    draft.code = ''
    draft.label = ''
    draft.sort_order = '0'
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-families">
    <AtriumPageHeader :title="t('showroom.catalog.families.title')" />

    <AtriumIntegrationScreen screen-key="showroom.families">
      <div class="space-y-4">
        <UCard data-testid="showroom-family-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.catalog.families.new') }}</h2>
          </template>
          <form class="flex flex-wrap items-start gap-3" data-testid="showroom-family-create-form" @submit.prevent="create">
            <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.catalog.families.code_hint')" :error="action.error('code')" required>
              <UInput v-model="draft.code" required class="w-56" data-testid="showroom-family-create-code" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
              <UInput v-model="draft.label" class="w-56" data-testid="showroom-family-create-label" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
              <UInput v-model="draft.sort_order" type="number" min="0" class="w-28" data-testid="showroom-family-create-sort" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" class="mt-field-label" data-testid="showroom-family-create-submit">{{ t('showroom.catalog.families.create') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <UCard>
          <form class="flex flex-wrap items-start gap-3" data-testid="showroom-families-filters" @submit.prevent="filter">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="search" class="w-56" data-testid="showroom-families-search" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter" class="mt-field-label" data-testid="showroom-families-filter">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" icon="i-lucide-x" class="mt-field-label" data-testid="showroom-families-clear" @click="clear">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <UTable :data="families" :columns="columns" data-testid="showroom-families-table">
              <template #empty>
                <AtriumState compact :title="t('showroom.catalog.families.empty')" data-testid="showroom-families-empty" />
              </template>
              <template #code-cell="{ row: { original: family } }">
                <span :data-testid="`showroom-family-row-${family.code}`">
                  <ULink :to="familyPath(family.code)" class="font-mono font-medium hover:underline">{{ family.code }}</ULink>
                </span>
              </template>
              <template #label-cell="{ row: { original: family } }">{{ labelOf(family.labels, locale, family.code) }}</template>
              <template #attributes_count-cell="{ row: { original: family } }">{{ family.attributes_count ?? t('showroom.catalog.none') }}</template>
              <template #label_attribute-cell="{ row: { original: family } }"><span class="font-mono text-xs">{{ family.label_attribute ?? t('showroom.catalog.none') }}</span></template>
            </UTable>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-families" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
