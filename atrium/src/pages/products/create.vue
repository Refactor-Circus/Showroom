<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import {
  ALL,
  labelOf,
  productPath,
  showroomData,
  useCatalogAction,
  usePickList,
  useShowroom,
  type CatalogList,
  type CatalogOne,
  type ShowroomFamily,
  type ShowroomProduct
} from '../../components/catalog/catalog'

/**
 * `showroom.product-create`: a new product (`products.store`) with its
 * identifier and, optionally, its family, ported from v1's
 * `products/create`. On success it opens the new product's page.
 */
const { t, locale } = useI18n()
const client = useShowroom()
const action = useCatalogAction()

const families = usePickList(async showroom => showroomData<CatalogList<ShowroomFamily>>(await showroom.GET('/families', { params: { query: { per_page: ALL } } })))

const form = reactive({ identifier: '', family: '' })

async function create(): Promise<void> {
  if (!client) {
    return
  }

  const created: { product: ShowroomProduct | null } = { product: null }

  const succeeded = await action.run(async () => {
    created.product = showroomData<CatalogOne<ShowroomProduct>>(await client.POST('/products', {
      body: { identifier: form.identifier.trim(), ...(form.family ? { family: form.family } : {}) }
    })).data
  })

  if (succeeded && created.product) {
    await navigateTo(productPath(created.product.id))
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-product-create">
    <h1 class="text-xl font-semibold">{{ t('showroom.catalog.products.new') }}</h1>

    <AtriumIntegrationScreen screen-key="showroom.product-create">
      <UCard>
        <form class="flex max-w-xl flex-col gap-4" data-testid="showroom-product-create-form" @submit.prevent="create">
          <CatalogActionError :action="action" />
          <UFormField :label="t('showroom.catalog.fields.identifier')" :hint="t('showroom.catalog.products.identifier_hint')" :error="action.error('identifier')" required>
            <UInput v-model="form.identifier" required data-testid="showroom-product-create-identifier" />
          </UFormField>
          <UFormField :label="t('showroom.catalog.fields.family')" :error="action.error('family')">
            <select v-model="form.family" class="w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-product-create-family">
              <option value="">{{ t('showroom.catalog.products.no_family') }}</option>
              <option v-for="family in families" :key="family.code" :value="family.code">{{ labelOf(family.labels, locale, family.code) }}</option>
            </select>
          </UFormField>
          <div>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-product-create-submit">{{ t('showroom.catalog.products.create') }}</UButton>
          </div>
        </form>
      </UCard>
    </AtriumIntegrationScreen>
  </div>
</template>
