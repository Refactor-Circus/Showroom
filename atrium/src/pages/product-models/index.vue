<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  formatDate,
  labelOf,
  productModelPath,
  queryValue,
  showroomData,
  useCatalogAction,
  useCatalogLoad,
  usePickList,
  type CatalogList,
  type ShowroomFamilyVariant,
  type ShowroomProductModel
} from '../../components/catalog/catalog'

/**
 * `showroom.product-models`: every product model (`product-models.index`),
 * ported from v1's `product-models/index`. The search and family variant
 * filters live in the URL and go to the endpoint as its query, a cursor page
 * at a time. A new root model is created with its code and family variant
 * (`product-models.store`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useCatalogAction()

const query = computed(() => {
  const search = queryValue(route.query.search)
  const variant = queryValue(route.query.family_variant)
  const cursor = queryValue(route.query.cursor)

  return {
    ...(search ? { search } : {}),
    ...(variant ? { family_variant: variant } : {}),
    ...(cursor ? { cursor } : {})
  }
})

const variants = usePickList(async client => showroomData<CatalogList<ShowroomFamilyVariant>>(await client.GET('/family-variants', { params: { query: { per_page: ALL } } })))

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomProductModel>>(await showroom.GET('/product-models', { params: { query: query.value } })), query)

const models = computed(() => data.value?.data ?? [])

const filters = reactive({ search: '', family_variant: '' })
const draft = reactive({ code: '', family_variant: '' })

watch(() => [route.query.search, route.query.family_variant], () => {
  filters.search = queryValue(route.query.search) ?? ''
  filters.family_variant = queryValue(route.query.family_variant) ?? ''
}, { immediate: true })

function filter(): void {
  router.replace({ query: { ...route.query, search: filters.search || undefined, family_variant: filters.family_variant || undefined, cursor: undefined } })
}

function clear(): void {
  router.replace({ query: {} })
}

async function create(): Promise<void> {
  if (client && await action.run(async () => showroomData(await client.POST('/product-models', {
    body: { code: draft.code.trim(), family_variant: draft.family_variant }
  })))) {
    draft.code = ''
    draft.family_variant = ''
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-product-models">
    <h1 class="text-xl font-semibold">{{ t('showroom.catalog.product_models.title') }}</h1>

    <AtriumIntegrationScreen screen-key="showroom.product-models">
      <div class="space-y-4">
        <UCard data-testid="showroom-product-model-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.catalog.product_models.new') }}</h2>
          </template>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-product-model-create-form" @submit.prevent="create">
            <UFormField :label="t('showroom.catalog.fields.code')" :error="action.error('code')" required>
              <UInput v-model="draft.code" required class="w-56" data-testid="showroom-product-model-create-code" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.family_variant')" :error="action.error('family_variant')" required>
              <select v-model="draft.family_variant" required class="w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-product-model-create-variant">
                <option value="" disabled>{{ t('showroom.catalog.product_models.choose_family_variant') }}</option>
                <option v-for="variant in variants" :key="variant.code" :value="variant.code">{{ labelOf(variant.labels, locale, variant.code) }}</option>
              </select>
            </UFormField>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-product-model-create-submit">{{ t('showroom.catalog.create') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <UCard>
          <form class="atrium-querybar" data-testid="showroom-product-models-filters" @submit.prevent="filter">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="filters.search" class="w-56" data-testid="showroom-product-models-search" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.family_variant')">
              <select v-model="filters.family_variant" class="w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-product-models-variant">
                <option value="">{{ t('showroom.catalog.filters.all_family_variants') }}</option>
                <option v-for="variant in variants" :key="variant.code" :value="variant.code">{{ labelOf(variant.labels, locale, variant.code) }}</option>
              </select>
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter" data-testid="showroom-product-models-filter">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" icon="i-lucide-x" data-testid="showroom-product-models-clear" @click="clear">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <p v-if="models.length === 0" class="atrium-empty" data-testid="showroom-product-models-empty">{{ t('showroom.catalog.product_models.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-product-models-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.family_variant') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.catalog.fields.updated') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="model in models" :key="model.code" :data-testid="`showroom-product-model-row-${model.code}`">
                  <td class="py-2 pr-3">
                    <RouterLink :to="productModelPath(model.code)" class="font-mono font-medium hover:underline">{{ model.code }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3 font-mono text-xs">{{ model.family_variant ?? t('showroom.catalog.none') }}</td>
                  <td class="py-2">{{ formatDate(model.updated_at) ?? t('showroom.catalog.none') }}</td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-product-models" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
