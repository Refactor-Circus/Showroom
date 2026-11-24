<script setup lang="ts">
// Not ported (D-032)
// Showroom's products, product models and families screens (`showroom.products`,
// `showroom.product-create`, `showroom.product`, `showroom.product-models`,
// `showroom.product-model`, `showroom.families`, `showroom.family`,
// `showroom.family-variant`) leave out these v1 features, which have no v2 API route:
// - Hiding buttons and cards, and the read-only form, by the viewer's abilities
//   (v1's `@showroomCan` and `ScreenAccess::allows()`). No route reports them, so
//   a 403 shows the no-access notice instead (D-031).
// - Showing the own-flags field only to those who may assign flags
//   (`FlagScreen::mayAssign()`). The field is always shown; Showroom answers 403
//   when an unassignable flag changes.
// - Publishing from draft or in review when `showroom.workflow.require_approval`
//   is off. No route reports the setting, so the page offers transitions as the
//   default (approval required) allows; Showroom still decides.
// - Version authors' names: `products.versions.index` names an author by type and
//   id only, so the history shows the id.
// - Uploading and linking an asset in one transaction (v1's `assets.upload`). It
//   is ported as `assets.store` then `assets.links.store`; a refused link leaves
//   the uploaded asset unlinked.
// - v1's `associations.add` and `associations.remove` routes. They are ported as
//   an update of the record (`products.update`, `product-models.update`) that
//   replaces the type's own targets, which is how v2 writes associations.
// - Links from associations and a model's variant products straight to a
//   product's page. They name products by identifier, which is not a lookup key
//   (`showroom.lookup.products`), so they link to the product list searched for it.
// - The flash messages after each action ("Product updated" and the like). The
//   page reloads instead.
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  formatDate,
  labelOf,
  labelValue,
  PRODUCT_STATUSES,
  productModelPath,
  productPath,
  queryPage,
  queryValue,
  showroomData,
  statusColor,
  untyped,
  useCatalogLoad,
  usePickList,
  type CatalogList,
  type ShowroomFamily,
  type ShowroomProduct
} from '../../components/catalog/catalog'

/**
 * `showroom.products`: every product (`products.index`), ported from v1's
 * `products/index`. The search, family, category, owner, workflow status and
 * enabled filters live in the URL and go to the endpoint as its query, a
 * numbered page at a time, as product search pages.
 */
const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const FILTERS = ['search', 'family', 'category', 'owner', 'status', 'enabled'] as const

const query = computed(() => {
  const params: Record<string, string | number> = {}

  for (const filter of FILTERS) {
    const value = queryValue(route.query[filter])

    if (value !== undefined) {
      params[filter] = filter === 'enabled' ? (value === '1' ? 1 : 0) : value
    }
  }

  const page = queryPage(route.query.page)

  return page > 1 ? { ...params, page } : params
})

const families = usePickList(async client => showroomData<CatalogList<ShowroomFamily>>(await client.GET('/families', { params: { query: { per_page: ALL } } })))

const { data, pending, failed, status } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomProduct>>(await showroom.GET('/products', { params: { query: untyped(query.value) } })), query)

const products = computed(() => data.value?.data ?? [])
const paging = computed(() => ({ current: data.value?.meta?.current_page ?? 1, last: data.value?.meta?.last_page ?? 1 }))

const filters = reactive<Record<(typeof FILTERS)[number], string>>({ search: '', family: '', category: '', owner: '', status: '', enabled: '' })

watch(() => FILTERS.map(filter => route.query[filter]), () => {
  for (const filter of FILTERS) {
    filters[filter] = queryValue(route.query[filter]) ?? ''
  }
}, { immediate: true })

function familyOf(code: string | null | undefined): ShowroomFamily | undefined {
  return code ? families.value.find(family => family.code === code) : undefined
}

function labelOfProduct(product: ShowroomProduct): string {
  return labelValue(product.values, familyOf(product.family)?.label_attribute, locale.value) ?? t('showroom.catalog.none')
}

function familyLabel(code: string | null | undefined): string {
  const family = familyOf(code)

  return family ? labelOf(family.labels, locale.value, family.code) : code || t('showroom.catalog.none')
}

function filter(): void {
  const next: Record<string, string | undefined> = {}

  for (const name of FILTERS) {
    next[name] = filters[name] || undefined
  }

  router.replace({ query: { ...route.query, ...next, page: undefined } })
}

function clear(): void {
  router.replace({ query: {} })
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-products">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold">{{ t('showroom.catalog.products.title') }}</h1>
      <div class="flex gap-2">
        <UButton to="/showroom/flags" variant="outline" icon="i-lucide-flag" data-testid="showroom-flag-definitions">{{ t('showroom.catalog.products.flag_definitions') }}</UButton>
        <UButton to="/showroom/products/create" icon="i-lucide-plus" data-testid="showroom-products-new">{{ t('showroom.catalog.products.new') }}</UButton>
      </div>
    </div>

    <AtriumIntegrationScreen screen-key="showroom.products">
      <div class="space-y-4">
        <UCard>
          <form class="atrium-querybar" data-testid="showroom-products-filters" @submit.prevent="filter">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="filters.search" name="search" class="w-64" data-testid="showroom-products-search" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.family')">
              <select v-model="filters.family" class="w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-products-family">
                <option value="">{{ t('showroom.catalog.filters.all_families') }}</option>
                <option v-for="family in families" :key="family.code" :value="family.code">{{ labelOf(family.labels, locale, family.code) }}</option>
              </select>
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.category')">
              <UInput v-model="filters.category" name="category" class="w-44" data-testid="showroom-products-category" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.owner')">
              <UInput v-model="filters.owner" name="owner" class="w-44" data-testid="showroom-products-owner" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.workflow')">
              <select v-model="filters.status" class="w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-products-status">
                <option value="">{{ t('showroom.catalog.filters.all_statuses') }}</option>
                <option v-for="choice in PRODUCT_STATUSES" :key="choice" :value="choice">{{ t(`showroom.statuses.${choice}`) }}</option>
              </select>
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.enabled')">
              <select v-model="filters.enabled" class="w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-products-enabled">
                <option value="">{{ t('showroom.catalog.filters.all_statuses') }}</option>
                <option value="1">{{ t('showroom.catalog.enabled') }}</option>
                <option value="0">{{ t('showroom.catalog.disabled') }}</option>
              </select>
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter" data-testid="showroom-products-filter">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" icon="i-lucide-x" data-testid="showroom-products-clear" @click="clear">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <p v-if="products.length === 0" class="atrium-empty" data-testid="showroom-products-empty">{{ t('showroom.catalog.products.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-products-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.identifier') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.family') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.parent') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.enabled') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.workflow') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.catalog.fields.updated') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="product in products" :key="product.id" :data-testid="`showroom-product-row-${product.id}`">
                  <td class="py-2 pr-3">
                    <RouterLink :to="productPath(product.id)" class="font-mono font-medium hover:underline">{{ product.identifier }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3">{{ labelOfProduct(product) }}</td>
                  <td class="py-2 pr-3">{{ familyLabel(product.family) }}</td>
                  <td class="py-2 pr-3 font-mono text-xs">
                    <RouterLink v-if="product.parent" :to="productModelPath(product.parent)" class="hover:underline">{{ product.parent }}</RouterLink>
                    <span v-else>{{ t('showroom.catalog.none') }}</span>
                  </td>
                  <td class="py-2 pr-3">
                    <UBadge :color="product.enabled ? 'success' : 'neutral'" variant="subtle" :data-status="product.enabled ? 'enabled' : 'disabled'">
                      {{ product.enabled ? t('showroom.catalog.enabled') : t('showroom.catalog.disabled') }}
                    </UBadge>
                  </td>
                  <td class="py-2 pr-3">
                    <span class="flex items-center gap-2">
                      <UBadge :color="statusColor(product.status)" variant="subtle" :data-status="product.status">{{ t(`showroom.statuses.${product.status}`) }}</UBadge>
                      <UBadge v-if="product.published_version" color="neutral" variant="outline">v{{ product.published_version }}</UBadge>
                    </span>
                  </td>
                  <td class="py-2">{{ formatDate(product.updated_at) ?? t('showroom.catalog.none') }}</td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :current="paging.current" :last="paging.last" testid="showroom-products" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
