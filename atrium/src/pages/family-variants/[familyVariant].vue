<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  familyPath,
  labelOf,
  productModelPath,
  showroomData,
  useCatalogAction,
  useCatalogLoad,
  type CatalogList,
  type CatalogOne,
  type ShowroomFamilyVariant,
  type ShowroomProductModel
} from '../../components/catalog/catalog'

/**
 * `showroom.family-variant`: one family variant (`family-variants.show`),
 * ported from v1's `family-variants/show`: its levels (the common
 * attributes, then each level's axes and attributes), its product models
 * (`product-models.index?family_variant=`), a link to its family, and delete
 * (confirmed).
 */
interface Loaded {
  variant: ShowroomFamilyVariant
  models: ShowroomProductModel[]
}

const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const code = computed(() => String(route.params.familyVariant))

const { client, data, pending, failed, status } = useCatalogLoad<Loaded>(async (showroom) => {
  const variant = showroomData<CatalogOne<ShowroomFamilyVariant>>(await showroom.GET('/family-variants/{familyVariant}', { params: { path: { familyVariant: code.value } } })).data
  const models = showroomData<CatalogList<ShowroomProductModel>>(await showroom.GET('/product-models', { params: { query: { family_variant: code.value, per_page: ALL } } })).data

  return { variant, models }
}, code)

const variant = computed(() => data.value?.variant ?? null)

function codes(list: readonly string[] | undefined): string {
  return list && list.length > 0 ? list.join(', ') : t('showroom.catalog.none')
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.catalog.family_variants.confirm_delete'))) {
    return
  }

  const family = variant.value?.family

  if (await action.run(async () => showroomData(await client.DELETE('/family-variants/{familyVariant}', { params: { path: { familyVariant: code.value } } })))) {
    await navigateTo(family ? familyPath(family) : '/showroom/families')
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-family-variant">
    <AtriumIntegrationScreen screen-key="showroom.family-variant" :record="code">
      <CatalogState :ready="!pending && variant !== null" :failed="failed" :status="status">
        <div v-if="variant" class="space-y-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h1 class="text-xl font-semibold" data-testid="showroom-family-variant-title">{{ labelOf(variant.labels, locale, variant.code) }}</h1>
              <p class="font-mono text-sm text-muted">{{ variant.code }}<template v-if="variant.family"> · {{ variant.family }}</template></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <UButton v-if="variant.family" :to="familyPath(variant.family)" variant="outline" icon="i-lucide-arrow-left" data-testid="showroom-family-variant-family">{{ variant.family }}</UButton>
              <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-family-variant-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
            </div>
          </div>

          <CatalogActionError :action="action" />

          <UCard data-testid="showroom-family-variant-levels">
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.catalog.family_variants.levels') }}</h2>
            </template>
            <table class="w-full text-left text-sm">
              <thead class="text-muted">
                <tr>
                  <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.family_variants.level') }}</th>
                  <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.families.axes') }}</th>
                  <th class="py-1 font-medium">{{ t('showroom.catalog.families.attributes') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr data-testid="showroom-level-common">
                  <td class="py-1 pr-3">{{ t('showroom.catalog.family_variants.common') }}</td>
                  <td class="py-1 pr-3">{{ t('showroom.catalog.none') }}</td>
                  <td class="py-1 font-mono text-xs">{{ codes(variant.common_attributes) }}</td>
                </tr>
                <tr v-for="level in variant.levels ?? []" :key="level.level" :data-testid="`showroom-level-${level.level}`">
                  <td class="py-1 pr-3">{{ level.level }}</td>
                  <td class="py-1 pr-3 font-mono text-xs">{{ codes(level.axes) }}</td>
                  <td class="py-1 font-mono text-xs">{{ codes(level.attributes) }}</td>
                </tr>
              </tbody>
            </table>
          </UCard>

          <UCard data-testid="showroom-family-variant-models">
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.catalog.product_models.title') }}</h2>
            </template>
            <p v-if="(data?.models ?? []).length === 0" class="text-sm text-muted">{{ t('showroom.catalog.product_models.empty') }}</p>
            <ul v-else class="flex flex-wrap gap-2">
              <li v-for="model in data?.models ?? []" :key="model.code">
                <RouterLink :to="productModelPath(model.code)" class="font-mono text-sm hover:underline" :data-testid="`showroom-family-variant-model-${model.code}`">{{ model.code }}</RouterLink>
              </li>
            </ul>
          </UCard>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
