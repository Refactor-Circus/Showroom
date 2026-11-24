<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui/components/Table.vue'
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

const filters = reactive<{ search: string, family_variant: string | null }>({ search: '', family_variant: '' })
const draft = reactive({ code: '', family_variant: '' })

const variantItems = computed(() => variants.value.map(variant => ({ label: labelOf(variant.labels, locale.value, variant.code), value: variant.code })))

const columns = computed<TableColumn<ShowroomProductModel>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { accessorKey: 'family_variant', header: t('showroom.catalog.fields.family_variant') },
  { accessorKey: 'updated_at', header: t('showroom.catalog.fields.updated') }
])

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
    <AtriumPageHeader :title="t('showroom.catalog.product_models.title')" />

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
              <USelect v-model="draft.family_variant" :items="variantItems" :placeholder="t('showroom.catalog.product_models.choose_family_variant')" required class="w-64" data-testid="showroom-product-model-create-variant" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-product-model-create-submit">{{ t('showroom.catalog.create') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <UCard>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-product-models-filters" @submit.prevent="filter">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="filters.search" class="w-56" data-testid="showroom-product-models-search" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.family_variant')">
              <USelect v-model="filters.family_variant" :items="[{ label: t('showroom.catalog.filters.all_family_variants'), value: null }, ...variantItems]" :placeholder="t('showroom.catalog.filters.all_family_variants')" class="w-64" data-testid="showroom-product-models-variant" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter" data-testid="showroom-product-models-filter">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" icon="i-lucide-x" data-testid="showroom-product-models-clear" @click="clear">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <UTable :data="models" :columns="columns" data-testid="showroom-product-models-table">
              <template #empty>
                <AtriumState compact :title="t('showroom.catalog.product_models.empty')" data-testid="showroom-product-models-empty" />
              </template>
              <template #code-cell="{ row: { original: model } }">
                <span :data-testid="`showroom-product-model-row-${model.code}`">
                  <ULink :to="productModelPath(model.code)" class="font-mono font-medium hover:underline">{{ model.code }}</ULink>
                </span>
              </template>
              <template #family_variant-cell="{ row: { original: model } }"><span class="font-mono text-xs">{{ model.family_variant ?? t('showroom.catalog.none') }}</span></template>
              <template #updated_at-cell="{ row: { original: model } }">{{ formatDate(model.updated_at) ?? t('showroom.catalog.none') }}</template>
            </UTable>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-product-models" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
