<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  blankToNull,
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  usePickList,
  type CatalogList
} from '../../components/catalog/catalog'
import { newLabels } from '../../components/structure/structure'
import { ownerPath, type ShowroomOwner, type ShowroomOwnerType } from '../../components/owners/owners'

/**
 * `showroom.owners`: every owner (`owners.index`), a cursor page at a time,
 * ported from v1's `owners/index`, with its type, parent and children
 * counted. The filters (search and type) go in the route's query and on to
 * the endpoint's; the owner type page links here with `?type=`. A new owner
 * is created (`owners.store`) with its code, type, parent code and its label
 * in the current locale, once an owner type exists.
 */
const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useCatalogAction()

const query = computed(() => {
  const filters: Record<string, string> = {}

  for (const key of ['search', 'type', 'cursor'] as const) {
    const value = queryValue(route.query[key])

    if (value) {
      filters[key] = value
    }
  }

  return filters
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomOwner>>(await showroom.GET('/owners', { params: { query: untyped(query.value) } })), query)

const owners = computed(() => data.value?.data ?? [])

const types = usePickList(async showroom => showroomData<CatalogList<ShowroomOwnerType>>(await showroom.GET('/owner-types', { params: { query: { per_page: ALL } } })))

const filters = reactive<{ search: string, type: string | null }>({ search: '', type: '' })

watch(() => route.query, () => {
  filters.search = queryValue(route.query.search) ?? ''
  filters.type = queryValue(route.query.type) ?? ''
}, { immediate: true })

function applyFilters(): void {
  router.replace({
    query: {
      ...(filters.search.trim() ? { search: filters.search.trim() } : {}),
      ...(filters.type ? { type: filters.type } : {})
    }
  })
}

function clearFilters(): void {
  router.replace({ query: {} })
}

const typeItems = computed(() => types.value.map(type => ({ label: labelOf(type.labels, locale.value, type.code), value: type.code })))

const columns = computed<TableColumn<ShowroomOwner>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { accessorKey: 'type', header: t('showroom.catalog.fields.type') },
  { accessorKey: 'parent', header: t('showroom.catalog.fields.parent') },
  { accessorKey: 'children_count', header: t('showroom.owners.children'), meta: { class: { th: 'text-right', td: 'text-right' } } },
  { accessorKey: 'enabled', header: t('showroom.catalog.fields.enabled') }
])

const draft = reactive({ code: '', type: '', parent: '', label: '' })

async function create(): Promise<void> {
  if (!client) {
    return
  }

  const parent = blankToNull(draft.parent)
  const body = {
    code: draft.code.trim(),
    type: draft.type,
    ...(parent ? { parent } : {}),
    ...newLabels(locale.value, draft.label)
  }

  if (await action.run(async () => showroomData(await client.POST('/owners', { body: untyped(body) })))) {
    draft.code = ''
    draft.parent = ''
    draft.label = ''
    await reload()
  }
}

function typeLabel(code: string | undefined): string {
  const type = types.value.find(entry => entry.code === code)

  return type ? labelOf(type.labels, locale.value, type.code) : code ?? t('showroom.catalog.none')
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-owners-page">
    <AtriumPageHeader :title="t('showroom.owners.title')" :description="t('showroom.owners.description')">
        <UButton to="/showroom/owner-types" variant="outline" icon="i-lucide-shapes" data-testid="showroom-owners-owner-types">{{ t('showroom.owner_types.title') }}</UButton>
        <UButton to="/showroom/flags" variant="outline" icon="i-lucide-flag" data-testid="showroom-owners-flags">{{ t('showroom.owners.flags') }}</UButton>
    </AtriumPageHeader>

    <AtriumIntegrationScreen screen-key="showroom.owners">
      <div class="space-y-4">
        <UCard data-testid="showroom-owner-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.owners.new') }}</h2>
          </template>
          <p v-if="types.length === 0" class="text-sm text-muted" data-testid="showroom-owners-no-types">{{ t('showroom.owners.no_types') }}</p>
          <form v-else class="flex flex-col gap-3" data-testid="showroom-owner-create-form" @submit.prevent="create">
            <CatalogActionError :action="action" />
            <div class="flex flex-wrap items-start gap-3">
              <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.owners.code_hint')" :error="action.error('code')" required>
                <UInput v-model="draft.code" required class="w-48" data-testid="showroom-owner-create-code" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.type')" :error="action.error('type')" required>
                <USelect v-model="draft.type" :items="typeItems" :placeholder="t('showroom.owners.choose_type')" required class="w-44" data-testid="showroom-owner-create-type" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.parent')" :hint="t('showroom.owners.parent_hint')" :error="action.error('parent')">
                <UInput v-model="draft.parent" class="w-44" data-testid="showroom-owner-create-parent" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="draft.label" class="w-48" data-testid="showroom-owner-create-label" />
              </UFormField>
              <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" class="mt-field-label" data-testid="showroom-owner-create-submit">{{ t('showroom.catalog.create') }}</UButton>
            </div>
          </form>
        </UCard>

        <UCard>
          <form class="flex flex-wrap items-start gap-3" data-testid="showroom-owners-filters" @submit.prevent="applyFilters">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="filters.search" class="w-56" data-testid="showroom-owners-search" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.type')">
              <AtriumSelect v-model="filters.type" :items="typeItems" :blank="t('showroom.owners.all_types')" :blank-value="null" class="w-44" data-testid="showroom-owners-type" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter" class="mt-field-label">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" color="neutral" icon="i-lucide-x" class="mt-field-label" @click="clearFilters">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <UTable :data="owners" :columns="columns" data-testid="showroom-owners-table">
              <template #empty>
                <AtriumState compact :title="t('showroom.owners.empty')" data-testid="showroom-owners-empty" />
              </template>
              <template #code-cell="{ row: { original: owner } }">
                <span :style="{ paddingLeft: `${owner.depth * 0.75}rem` }" :data-testid="`showroom-owner-row-${owner.code}`">
                  <ULink :to="ownerPath(owner.id)" class="font-mono font-medium hover:underline">{{ owner.code }}</ULink>
                </span>
              </template>
              <template #label-cell="{ row: { original: owner } }">{{ labelOf(owner.labels, locale, owner.code) }}</template>
              <template #type-cell="{ row: { original: owner } }">
                <span class="font-mono text-xs" :title="typeLabel(owner.type)">{{ owner.type ?? t('showroom.catalog.none') }}</span>
              </template>
              <template #parent-cell="{ row: { original: owner } }">
                <span class="font-mono text-xs">{{ owner.parent ?? t('showroom.catalog.none') }}</span>
              </template>
              <template #children_count-cell="{ row: { original: owner } }">
                <span class="tabular-nums">{{ owner.children_count ?? 0 }}</span>
              </template>
              <template #enabled-cell="{ row: { original: owner } }">
                <UBadge :color="owner.enabled ? 'success' : 'neutral'" variant="subtle">
                  {{ owner.enabled ? t('showroom.catalog.enabled') : t('showroom.catalog.disabled') }}
                </UBadge>
              </template>
            </UTable>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-owners" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
