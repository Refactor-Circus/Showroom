<script setup lang="ts">
import AxesFields from '../../components/catalog/AxesFields.vue'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogAssets from '../../components/catalog/CatalogAssets.vue'
import CatalogAssociations from '../../components/catalog/CatalogAssociations.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import InheritedValues from '../../components/catalog/InheritedValues.vue'
import SlotPicker from '../../components/catalog/SlotPicker.vue'
import ValueEditor from '../../components/catalog/ValueEditor.vue'
import {
  ALL,
  blankToNull,
  categoryPath,
  familyVariantPath,
  ownAssociations,
  ownCategories,
  ownQuantified,
  ownValues,
  productModelPath,
  productSearchPath,
  showroomData,
  splitCodes,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  usePickList,
  type CatalogList,
  type CatalogOne,
  type ShowroomAssociationType,
  type ShowroomFamilyVariant,
  type ShowroomProductModel
} from '../../components/catalog/catalog'
import {
  axesBody,
  axesInputs,
  levelAttributes,
  levelAxes,
  useAttributeCatalog,
  useEditingSlot,
  valueInputs,
  valuesBody,
  type ValueInput
} from '../../components/catalog/values'

/**
 * `showroom.product-model`: one product model (`product-models.show`), ported
 * from v1's `product-models/show`. It edits in one save
 * (`product-models.update`) its owner (a root model only), own categories and
 * the values its level sets, in the picked locale and channel; assets upload
 * and link, associations add and remove, and delete is confirmed. A model
 * holding products lists its variants and makes a new one
 * (`products.store` with the model as parent and the last level's axes);
 * any other lists its sub-models and makes one (`product-models.store` with
 * the first level's axes). A sub-model's inherited values and categories
 * come from its parent model.
 */
interface Loaded {
  model: ShowroomProductModel
  parent: ShowroomProductModel | null
  variant: ShowroomFamilyVariant | null
}

const { t } = useI18n()
const route = useRoute()
const action = useCatalogAction()
const childAction = useCatalogAction()

const code = computed(() => String(route.params.productModel))

const { client, data, pending, failed, status, reload } = useCatalogLoad<Loaded>(async (showroom) => {
  const model = showroomData<CatalogOne<ShowroomProductModel>>(await showroom.GET('/product-models/{productModel}', { params: { path: { productModel: code.value } } })).data
  const parent = model.parent
    ? showroomData<CatalogOne<ShowroomProductModel>>(await showroom.GET('/product-models/{productModel}', { params: { path: { productModel: model.parent } } })).data
    : null
  const variant = model.family_variant
    ? showroomData<CatalogOne<ShowroomFamilyVariant>>(await showroom.GET('/family-variants/{familyVariant}', { params: { path: { familyVariant: model.family_variant } } })).data
    : null

  return { model, parent, variant }
}, code)

const model = computed(() => data.value?.model ?? null)
const parent = computed(() => data.value?.parent ?? null)
const variant = computed(() => data.value?.variant ?? null)

const associationTypes = usePickList(async showroom => showroomData<CatalogList<ShowroomAssociationType>>(await showroom.GET('/association-types', { params: { query: { per_page: ALL } } })))

const levels = computed(() => variant.value?.levels?.length ?? 1)
/** Whether the model's children are products (the last model level) rather than sub-models. */
const holdsProducts = computed(() => (model.value?.level ?? 0) + 1 >= levels.value)

const formCodes = computed(() => model.value ? levelAttributes(variant.value, model.value.level) : [])
const axisCodes = computed(() => model.value ? levelAxes(variant.value, holdsProducts.value ? levels.value : 1) : [])

const catalog = useAttributeCatalog(() => [...formCodes.value, ...axisCodes.value])
const editorAttributes = computed(() => catalog.pick(formCodes.value))
const axes = computed(() => catalog.pick(axisCodes.value))

const inherited = computed(() => parent.value?.values ?? {})
const own = computed(() => ownValues(model.value?.values, inherited.value))
const inheritedCategories = computed(() => parent.value?.categories ?? [])

const slots = useEditingSlot()

const form = reactive({ owner: '', categories: '' })
const inputs = ref<Record<string, ValueInput>>({})
const child = reactive({ key: '' })
const childAxes = ref<Record<string, ValueInput>>({})

watch([data, () => slots.slot.value], () => {
  const current = model.value

  if (!current) {
    return
  }

  form.owner = current.owner ?? ''
  form.categories = ownCategories(current.categories, inheritedCategories.value).join(', ')
  inputs.value = valueInputs(editorAttributes.value, own.value, slots.slot.value)
}, { immediate: true })

watch(editorAttributes, (attributes) => {
  const missing = attributes.filter(attribute => !(attribute.code in inputs.value))

  if (missing.length > 0) {
    inputs.value = { ...valueInputs(missing, own.value, slots.slot.value), ...inputs.value }
  }
})

watch(axes, (list) => {
  const missing = list.filter(axis => !(axis.code in childAxes.value))

  if (missing.length > 0) {
    childAxes.value = { ...axesInputs(missing), ...childAxes.value }
  }
}, { immediate: true })

async function update(body: Record<string, unknown>): Promise<unknown> {
  if (!client) {
    return null
  }

  return showroomData(await client.PATCH('/product-models/{productModel}', { params: { path: { productModel: code.value } }, body: untyped(body) }))
}

async function save(): Promise<void> {
  const current = model.value

  if (!current) {
    return
  }

  const body: Record<string, unknown> = {
    ...(current.level === 0 ? { owner: blankToNull(form.owner) } : {}),
    categories: splitCodes(form.categories),
    values: valuesBody(editorAttributes.value, inputs.value, slots.slot.value)
  }

  if (await action.run(() => update(body))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.catalog.product_models.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/product-models/{productModel}', { params: { path: { productModel: code.value } } })))) {
    await navigateTo('/showroom/product-models')
  }
}

async function createChild(): Promise<void> {
  if (!client) {
    return
  }

  const values = axesBody(axes.value, childAxes.value)
  const key = child.key.trim()

  const succeeded = await childAction.run(async () => holdsProducts.value
    ? showroomData(await client.POST('/products', { body: untyped({ identifier: key, parent: code.value, values }) }))
    : showroomData(await client.POST('/product-models', { body: untyped({ code: key, parent: code.value, values }) })))

  if (succeeded) {
    child.key = ''
    childAxes.value = axesInputs(axes.value)
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-product-model">
    <AtriumIntegrationScreen screen-key="showroom.product-model" :record="code">
      <CatalogState :ready="!pending && model !== null" :failed="failed" :status="status">
        <div v-if="model" class="space-y-5">
          <AtriumPageHeader>
            <template #title>
              <span class="font-mono" data-testid="showroom-product-model-title">{{ model.code }}</span>
            </template>
            <template #description>
              <ULink v-if="model.family_variant" :to="familyVariantPath(model.family_variant)" class="font-mono hover:underline">{{ model.family_variant }}</ULink>
              · {{ t('showroom.catalog.product_models.level', { level: model.level }) }}
            </template>
            <UButton v-if="model.parent" :to="productModelPath(model.parent)" variant="outline" icon="i-lucide-arrow-left" data-testid="showroom-product-model-parent">{{ model.parent }}</UButton>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-product-model-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </AtriumPageHeader>

          <SlotPicker :locales="slots.locales.value" :channels="slots.channels.value" :editing="slots.slot.value" @pick="slots.pick" />

          <form class="space-y-5" data-testid="showroom-product-model-form" @submit.prevent="save">
            <CatalogActionError :action="action" />

            <UCard v-if="model.level === 0">
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.products.details') }}</h2>
              </template>
              <UFormField :label="t('showroom.catalog.fields.owner')" :hint="t('showroom.catalog.products.owner_hint')" :error="action.error('owner')">
                <UInput v-model="form.owner" class="w-56" data-testid="showroom-product-model-owner" />
              </UFormField>
            </UCard>

            <UCard>
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.categories.title') }}</h2>
              </template>
              <div class="flex flex-col gap-2">
                <UFormField :label="t('showroom.catalog.categories.title')" :hint="t('showroom.catalog.categories.hint')" :error="action.error('categories')">
                  <UInput v-model="form.categories" class="w-full" data-testid="showroom-product-model-categories" />
                </UFormField>
                <p v-if="inheritedCategories.length > 0" class="text-xs" data-testid="showroom-inherited-categories">
                  {{ t('showroom.catalog.categories.inherited') }}:
                  <template v-for="(category, index) in inheritedCategories" :key="category">
                    <ULink :to="categoryPath(category)" class="font-mono hover:underline">{{ category }}</ULink><span v-if="index < inheritedCategories.length - 1">, </span>
                  </template>
                </p>
              </div>
            </UCard>

            <UCard>
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.values.title') }}</h2>
              </template>
              <ValueEditor v-model="inputs" :attributes="editorAttributes" :editing="slots.slot.value" :error="action.error" />
            </UCard>

            <div>
              <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-product-model-save">{{ t('showroom.catalog.save') }}</UButton>
            </div>
          </form>

          <CatalogAssets :assets="model.assets ?? []" link-type="product_model" :link-target="model.code" @changed="reload" />

          <CatalogAssociations
            :associations="model.associations ?? {}"
            :quantified="model.quantified_associations ?? {}"
            :own-associations="ownAssociations(model.associations, parent?.associations)"
            :own-quantified="ownQuantified(model.quantified_associations, parent?.quantified_associations)"
            :types="associationTypes"
            :save="update"
            @changed="reload"
          />

          <InheritedValues :values="inherited" />

          <UCard :data-testid="holdsProducts ? 'showroom-variant-products' : 'showroom-sub-models'">
            <template #header>
              <h2 class="font-semibold">{{ holdsProducts ? t('showroom.catalog.product_models.variant_products') : t('showroom.catalog.product_models.sub_models') }}</h2>
            </template>
            <div class="space-y-4">
              <ul v-if="holdsProducts && (model.products ?? []).length > 0" class="flex flex-wrap gap-2">
                <li v-for="identifier in model.products ?? []" :key="identifier">
                  <ULink :to="productSearchPath(identifier)" class="font-mono text-sm hover:underline" :data-testid="`showroom-variant-product-${identifier}`">{{ identifier }}</ULink>
                </li>
              </ul>
              <ul v-if="!holdsProducts && (model.children ?? []).length > 0" class="flex flex-wrap gap-2">
                <li v-for="childCode in model.children ?? []" :key="childCode">
                  <ULink :to="productModelPath(childCode)" class="font-mono text-sm hover:underline" :data-testid="`showroom-sub-model-${childCode}`">{{ childCode }}</ULink>
                </li>
              </ul>

              <CatalogActionError :action="childAction" />

              <form class="flex flex-wrap items-end gap-3" data-testid="showroom-child-create-form" @submit.prevent="createChild">
                <UFormField
                  :label="holdsProducts ? t('showroom.catalog.fields.identifier') : t('showroom.catalog.fields.code')"
                  :error="childAction.error(holdsProducts ? 'identifier' : 'code')"
                  required
                >
                  <UInput v-model="child.key" required class="w-56" data-testid="showroom-child-key" />
                </UFormField>
                <AxesFields v-model="childAxes" :axes="axes" testid="showroom-child" :error="childAction.error" />
                <UButton type="submit" icon="i-lucide-plus" :loading="childAction.busy.value" data-testid="showroom-child-create">
                  {{ holdsProducts ? t('showroom.catalog.product_models.add_variant') : t('showroom.catalog.product_models.add_sub_model') }}
                </UButton>
              </form>
            </div>
          </UCard>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
