<script setup lang="ts">
// Not ported (D-032)
// Showroom's attributes, categories, channels and flags screens (`showroom.attributes`,
// `showroom.attribute-create`, `showroom.attribute`, `showroom.attribute-groups`,
// `showroom.attribute-group`, `showroom.association-types`, `showroom.categories`,
// `showroom.category`, `showroom.channels`, `showroom.channel`, `showroom.flags`,
// `showroom.flag`) leave out these v1 features, which have no v2 API route. Every
// v1 action of these screens (create, update and delete of each record; options
// added and deleted; locales added and deleted) has its v2 route and is ported.
// - Hiding the create, save and delete controls, and the read-only attribute form,
//   by the viewer's abilities (v1's `@showroomCan` and `ScreenAccess::allows()`).
//   No route reports them, so a 403 shows the no-access notice instead (D-031).
// - Hiding the channels screen's locales card from those who may not list locales
//   (`@showroomCan('viewAny', LocaleModel::class)`). The card shows its own
//   no-access state when `locales.index` answers 403.
// - The flash messages after each action ("Attribute updated" and the like). The
//   page reloads instead.
import type { TableColumn } from '@refactor-circus/atrium/ui'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  attributePath,
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogLoad,
  usePickList,
  type CatalogList,
  type ShowroomAttribute
} from '../../components/catalog/catalog'
import { ATTRIBUTE_FLAGS, ATTRIBUTE_TYPES, type ShowroomAttributeGroup } from '../../components/structure/structure'

/**
 * `showroom.attributes`: every attribute (`attributes.index`), ported from
 * v1's `attributes/index`. The search, type and group filters live in the
 * URL and go to the endpoint as its query, a cursor page at a time. The
 * groups come from `attribute-groups.index`.
 */
const FILTERS = ['search', 'type', 'group'] as const

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const query = computed(() => {
  const entries = [...FILTERS, 'cursor']
    .map((name: string) => [name, queryValue(route.query[name])] as const)
    .filter((entry): entry is readonly [string, string] => entry[1] !== undefined)

  return Object.fromEntries(entries)
})

const { data, pending, failed, status } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomAttribute>>(await showroom.GET('/attributes', { params: { query: untyped(query.value) } })), query)

const groups = usePickList<ShowroomAttributeGroup>(async showroom =>
  showroomData<CatalogList<ShowroomAttributeGroup>>(await showroom.GET('/attribute-groups', { params: { query: { per_page: ALL } } })))

const attributes = computed(() => data.value?.data ?? [])
const filters = reactive<Record<(typeof FILTERS)[number], string | null>>({ search: '', type: '', group: '' })

watch(() => route.query, () => {
  for (const name of FILTERS) {
    filters[name] = queryValue(route.query[name]) ?? ''
  }
}, { immediate: true })

const typeItems = computed(() => ATTRIBUTE_TYPES.map(type => ({ label: type, value: type })))

const groupItems = computed(() => groups.value.map(group => ({ label: labelOf(group.labels, locale.value, group.code), value: group.code })))

const columns = computed<TableColumn<ShowroomAttribute>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { accessorKey: 'type', header: t('showroom.catalog.fields.type') },
  { accessorKey: 'group', header: t('showroom.catalog.fields.group') },
  { id: 'switches', header: t('showroom.structure.attributes.switches') }
])

function groupLabel(code: string | null | undefined): string {
  if (!code) {
    return t('showroom.catalog.none')
  }

  const group = groups.value.find(entry => entry.code === code)

  return group ? labelOf(group.labels, locale.value, code) : code
}

function filter(): void {
  router.replace({ query: Object.fromEntries(FILTERS.map(name => [name, filters[name] || undefined])) })
}

function clear(): void {
  router.replace({ query: {} })
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-attributes">
    <AtriumPageHeader :title="t('showroom.structure.attributes.title')">
      <UButton to="/showroom/attributes/create" icon="i-lucide-plus" data-testid="showroom-attribute-new">{{ t('showroom.structure.attributes.new') }}</UButton>
    </AtriumPageHeader>

    <AtriumIntegrationScreen screen-key="showroom.attributes">
      <div class="space-y-4">
        <UCard>
          <form class="flex flex-wrap items-start gap-3" data-testid="showroom-attributes-filters" @submit.prevent="filter">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="filters.search" class="w-56" data-testid="showroom-attributes-search" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.type')">
              <AtriumSelect v-model="filters.type" :items="typeItems" :blank="t('showroom.structure.attributes.all_types')" :blank-value="null" class="w-44" data-testid="showroom-attributes-type" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.group')">
              <AtriumSelect v-model="filters.group" :items="groupItems" :blank="t('showroom.structure.attributes.all_groups')" :blank-value="null" class="w-48" data-testid="showroom-attributes-group" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter" class="mt-field-label" data-testid="showroom-attributes-filter">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" icon="i-lucide-x" class="mt-field-label" data-testid="showroom-attributes-clear" @click="clear">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <UTable :data="attributes" :columns="columns" data-testid="showroom-attributes-table">
              <template #empty>
                <AtriumState compact :title="t('showroom.structure.attributes.empty')" data-testid="showroom-attributes-empty" />
              </template>
              <template #code-cell="{ row: { original: attribute } }">
                <span :data-testid="`showroom-attribute-row-${attribute.code}`">
                  <ULink :to="attributePath(attribute.code)" class="font-mono font-medium hover:underline">{{ attribute.code }}</ULink>
                </span>
              </template>
              <template #label-cell="{ row: { original: attribute } }">{{ labelOf(attribute.labels, locale, attribute.code) }}</template>
              <template #type-cell="{ row: { original: attribute } }"><UBadge color="neutral" variant="subtle">{{ attribute.type }}</UBadge></template>
              <template #group-cell="{ row: { original: attribute } }">{{ groupLabel(attribute.group) }}</template>
              <template #switches-cell="{ row: { original: attribute } }">
                <span class="flex flex-wrap gap-1">
                  <template v-for="flag in ATTRIBUTE_FLAGS" :key="flag">
                    <UBadge v-if="attribute[flag]" color="neutral" variant="outline">{{ t(`showroom.structure.attributes.${flag}`) }}</UBadge>
                  </template>
                </span>
              </template>
            </UTable>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-attributes" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
