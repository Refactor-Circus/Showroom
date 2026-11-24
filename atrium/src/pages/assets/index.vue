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

const filters = reactive({ search: '', type: '', product: '' })

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
const fileInput = useTemplateRef<HTMLInputElement>('fileInput')

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

    if (fileInput.value) {
      fileInput.value.value = ''
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
    <div class="flex flex-wrap items-start justify-between gap-3">
      <h1 class="text-xl font-semibold">{{ t('showroom.assets.title') }}</h1>
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
    </div>

    <AtriumIntegrationScreen screen-key="showroom.assets">
      <div class="space-y-4">
        <UCard data-testid="showroom-asset-upload-card">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.assets.upload') }}</h2>
          </template>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-asset-upload-form" @submit.prevent="upload">
            <UFormField :label="t('showroom.assets.file')" :error="action.error('file')" required>
              <input ref="fileInput" type="file" required class="text-sm" data-testid="showroom-asset-upload-file" @change="choose">
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
          <form class="atrium-querybar" data-testid="showroom-assets-filters" @submit.prevent="applyFilters">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="filters.search" class="w-56" data-testid="showroom-assets-search" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.type')">
              <select v-model="filters.type" class="w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-assets-type">
                <option value="">{{ t('showroom.assets.all_types') }}</option>
                <option v-for="filter in ASSET_TYPE_FILTERS" :key="filter.value" :value="filter.value">{{ t(`showroom.assets.types.${filter.key}`) }}</option>
              </select>
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
            <p v-if="assets.length === 0" class="atrium-empty" data-testid="showroom-assets-empty">{{ t('showroom.assets.empty') }}</p>
            <div v-else-if="view === 'grid'" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5" data-testid="showroom-assets-grid">
              <div
                v-for="asset in assets"
                :key="asset.code"
                class="rounded-md border border-default p-2 hover:border-primary"
                :data-testid="`showroom-asset-tile-${asset.code}`"
              >
                <RouterLink :to="assetPath(asset.code)" class="flex flex-col gap-2">
                  <AssetThumbnail :asset="asset" :caption="asset.code" />
                </RouterLink>
              </div>
            </div>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-assets-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.type') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.assets.size') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.catalog.fields.updated') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="asset in assets" :key="asset.code" :data-testid="`showroom-asset-row-${asset.code}`">
                  <td class="py-2 pr-3">
                    <RouterLink :to="assetPath(asset.code)" class="font-mono font-medium hover:underline">{{ asset.code }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3">{{ labelFor(asset) }}</td>
                  <td class="py-2 pr-3 font-mono text-xs">{{ asset.mime_type ?? t('showroom.catalog.none') }}</td>
                  <td class="py-2 pr-3 tabular-nums">{{ formatSize(asset.size) }}</td>
                  <td class="py-2 text-muted">{{ formatDate(asset.updated_at) ?? t('showroom.catalog.none') }}</td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-assets" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
