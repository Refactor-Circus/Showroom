<script setup lang="ts">
// Not ported (D-032)
// Showroom's v2 API has no route for these v1 actions and details of the
// assets, owners and transfers screens, so they are left out:
// - Hiding each form and button by policy (`@showroomCan`): every action
//   shows, and Showroom's 403 shows the no-access notice instead (D-031).
// - The transfers list's result column (an export's file asset and record
//   count, an import's succeeded/failed/total counts): `impex.runs.index`
//   sends no step results. A failed run still shows its error message.
// - The owner type beside each code of an owner's chain and children:
//   `owners.show` names them by code only.
import type { TableColumn } from '@refactor-circus/atrium/ui'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  formatDate,
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogList
} from '../../components/catalog/catalog'
import AssetThumbnail from '../../components/assets/AssetThumbnail.vue'
import { ASSET_TYPE_FILTERS, assetPath, formatSize, type AssetView, type ShowroomAsset } from '../../components/assets/assets'

/**
 * `showroom.assets`: every asset (`assets.index`), a cursor page at a time,
 * ported from v1's `assets/index`, as tiles or as a list (`?view=list`, kept
 * in the route only). The filters (search, file type and product) go in the
 * route's query and on to the endpoint's. An upload (`assets.store`) sends
 * the file as multipart, with the code and the label in the current locale
 * when typed; Showroom names the asset after the file otherwise.
 */
const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useCatalogAction()

const query = computed(() => {
  const filters: Record<string, string> = {}

  for (const key of ['search', 'type', 'product', 'cursor'] as const) {
    const value = queryValue(route.query[key])

    if (value) {
      filters[key] = value
    }
  }

  return filters
})

// Reload only when what Showroom is asked changes, not when the view does.
const queryKey = computed(() => JSON.stringify(query.value))

const view = computed<AssetView>(() => queryValue(route.query.view) === 'list' ? 'list' : 'grid')

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomAsset>>(await showroom.GET('/assets', { params: { query: untyped(query.value) } })), queryKey)

const assets = computed(() => data.value?.data ?? [])

const filters = reactive<{ search: string, type: string | null, product: string }>({ search: '', type: '', product: '' })

watch(() => route.query, () => {
  filters.search = queryValue(route.query.search) ?? ''
  filters.type = queryValue(route.query.type) ?? ''
  filters.product = queryValue(route.query.product) ?? ''
}, { immediate: true })

function applyFilters(): void {
  router.replace({
    query: {
      ...(view.value === 'list' ? { view: 'list' } : {}),
      ...(filters.search.trim() ? { search: filters.search.trim() } : {}),
      ...(filters.type ? { type: filters.type } : {}),
      ...(filters.product.trim() ? { product: filters.product.trim() } : {})
    }
  })
}

function clearFilters(): void {
  router.replace({ query: view.value === 'list' ? { view: 'list' } : {} })
}

function show(next: AssetView): void {
  router.replace({ query: { ...route.query, view: next === 'list' ? 'list' : undefined } })
}

const draft = reactive({ code: '', label: '' })
const file = shallowRef<File | null>(null)
const fileInput = useTemplateRef<{ inputRef: HTMLInputElement | null }>('fileInput')

const typeItems = computed(() => ASSET_TYPE_FILTERS.map(filter => ({ label: t(`showroom.assets.types.${filter.key}`), value: filter.value })))

const columns = computed<TableColumn<ShowroomAsset>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { accessorKey: 'mime_type', header: t('showroom.catalog.fields.type') },
  { accessorKey: 'size', header: t('showroom.assets.size'), meta: { class: { th: 'text-right', td: 'text-right' } } },
  { accessorKey: 'updated_at', header: t('showroom.catalog.fields.updated') }
])

function choose(event: Event): void {
  file.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function upload(): Promise<void> {
  const chosen = file.value

  if (!client || !chosen) {
    return
  }

  const form = new FormData()
  form.append('file', chosen)

  if (draft.code.trim()) {
    form.append('code', draft.code.trim())
  }

  if (draft.label.trim()) {
    form.append(`labels[${locale.value}]`, draft.label.trim())
  }

  const uploaded = await action.run(async () => showroomData(await client.POST('/assets', {
    body: form as never,
    bodySerializer: body => body as FormData
  })))

  if (uploaded) {
    draft.code = ''
    draft.label = ''
    file.value = null

    if (fileInput.value?.inputRef) {
      fileInput.value.inputRef.value = ''
    }

    await reload()
  }
}

function labelFor(asset: ShowroomAsset): string {
  return labelOf(asset.labels, locale.value, asset.code)
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-assets-page">
    <AtriumPageHeader :title="t('showroom.assets.title')">
      <div class="flex gap-1">
        <UButton
          :variant="view === 'grid' ? 'solid' : 'ghost'"
          color="neutral"
          size="sm"
          icon="i-lucide-layout-grid"
          :aria-label="t('showroom.assets.grid')"
          data-testid="showroom-assets-view-grid"
          @click="show('grid')"
        />
        <UButton
          :variant="view === 'list' ? 'solid' : 'ghost'"
          color="neutral"
          size="sm"
          icon="i-lucide-list"
          :aria-label="t('showroom.assets.list')"
          data-testid="showroom-assets-view-list"
          @click="show('list')"
        />
      </div>
    </AtriumPageHeader>

    <AtriumIntegrationScreen screen-key="showroom.assets">
      <div class="space-y-4">
        <UCard data-testid="showroom-asset-upload-card">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.assets.upload') }}</h2>
          </template>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-asset-upload-form" @submit.prevent="upload">
            <UFormField :label="t('showroom.assets.file')" :error="action.error('file')" required>
              <UInput ref="fileInput" type="file" required data-testid="showroom-asset-upload-file" @change="choose" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.assets.code_hint')" :error="action.error('code')">
              <UInput v-model="draft.code" class="w-48" data-testid="showroom-asset-upload-code" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
              <UInput v-model="draft.label" class="w-56" data-testid="showroom-asset-upload-label" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-upload" :loading="action.busy.value" data-testid="showroom-asset-upload-submit">{{ t('showroom.assets.upload_button') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <UCard>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-assets-filters" @submit.prevent="applyFilters">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="filters.search" class="w-56" data-testid="showroom-assets-search" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.type')">
              <AtriumSelect v-model="filters.type" :items="typeItems" :blank="t('showroom.assets.all_types')" :blank-value="null" class="w-40" data-testid="showroom-assets-type" />
            </UFormField>
            <UFormField :label="t('showroom.assets.product')">
              <UInput v-model="filters.product" class="w-44" data-testid="showroom-assets-product" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" color="neutral" icon="i-lucide-x" @click="clearFilters">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <div v-if="view === 'grid'" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5" data-testid="showroom-assets-grid">
              <AtriumState v-if="assets.length === 0" compact class="col-span-full" :title="t('showroom.assets.empty')" data-testid="showroom-assets-empty" />
              <div
                v-for="asset in assets"
                :key="asset.code"
                class="rounded-md border border-default p-2 hover:border-primary"
                :data-testid="`showroom-asset-tile-${asset.code}`"
              >
                <ULink :to="assetPath(asset.code)" class="flex flex-col gap-2">
                  <AssetThumbnail :asset="asset" :caption="asset.code" />
                </ULink>
              </div>
            </div>
            <UTable v-else :data="assets" :columns="columns" data-testid="showroom-assets-table">
              <template #empty>
                <AtriumState compact :title="t('showroom.assets.empty')" data-testid="showroom-assets-empty" />
              </template>
              <template #code-cell="{ row: { original: asset } }">
                <span :data-testid="`showroom-asset-row-${asset.code}`">
                  <ULink :to="assetPath(asset.code)" class="font-mono font-medium hover:underline">{{ asset.code }}</ULink>
                </span>
              </template>
              <template #label-cell="{ row: { original: asset } }">{{ labelFor(asset) }}</template>
              <template #mime_type-cell="{ row: { original: asset } }">
                <span class="font-mono text-xs">{{ asset.mime_type ?? t('showroom.catalog.none') }}</span>
              </template>
              <template #size-cell="{ row: { original: asset } }">
                <span class="tabular-nums">{{ formatSize(asset.size) }}</span>
              </template>
              <template #updated_at-cell="{ row: { original: asset } }">
                <span class="text-muted">{{ formatDate(asset.updated_at) ?? t('showroom.catalog.none') }}</span>
              </template>
            </UTable>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-assets" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
