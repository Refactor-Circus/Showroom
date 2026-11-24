<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogAssets from '../../components/catalog/CatalogAssets.vue'
import CatalogAssociations from '../../components/catalog/CatalogAssociations.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import ExternalIds from '../../components/catalog/ExternalIds.vue'
import InheritedValues from '../../components/catalog/InheritedValues.vue'
import ProductWorkflow from '../../components/catalog/ProductWorkflow.vue'
import SlotPicker from '../../components/catalog/SlotPicker.vue'
import ValueEditor from '../../components/catalog/ValueEditor.vue'
import {
  ALL,
  blankToNull,
  categoryPath,
  externalIdsBody,
  externalIdsForm,
  labelOf,
  ownAssociations,
  ownCategories,
  ownQuantified,
  ownValues,
  productModelPath,
  showroomData,
  splitCodes,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  usePickList,
  type CatalogList,
  type CatalogOne,
  type ExternalIdsForm,
  type ShowroomAssociationType,
  type ShowroomFamily,
  type ShowroomFamilyVariant,
  type ShowroomFlag,
  type ShowroomProduct,
  type ShowroomProductModel,
  type ShowroomVersion
} from '../../components/catalog/catalog'
import {
  levelAttributes,
  useAttributeCatalog,
  useEditingSlot,
  valueInputs,
  valuesBody,
  type ValueInput
} from '../../components/catalog/values'

/**
 * `showroom.product`: one product (`products.show`), ported from v1's
 * `products/show` and `products/workflow`. It shows the workflow, the
 * completeness and history with revert, and edits in one save
 * (`products.update`): the family and owner (not on a variant, which takes
 * its model's), enabled, its external ids and own flags, its own categories
 * and the values of the picked locale and channel. Assets upload and link,
 * associations add and remove, and delete is confirmed. A variant's
 * inherited values, categories and associations come from its product model
 * (`product-models.show`), and the attributes it sets from its family
 * variant (`family-variants.show`).
 */
interface Loaded {
  product: ShowroomProduct
  versions: ShowroomVersion[]
  parent: ShowroomProductModel | null
  variant: ShowroomFamilyVariant | null
  family: ShowroomFamily | null
}

const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const productId = computed(() => String(route.params.product))

const { client, data, pending, failed, status, reload } = useCatalogLoad<Loaded>(async (showroom) => {
  const product = showroomData<CatalogOne<ShowroomProduct>>(await showroom.GET('/products/{product}', { params: { path: { product: productId.value } } })).data
  const versions = showroomData<CatalogList<ShowroomVersion>>(await showroom.GET('/products/{product}/versions', {
    params: { path: { product: productId.value }, query: { per_page: 10 } }
  })).data

  let parent: ShowroomProductModel | null = null
  let variant: ShowroomFamilyVariant | null = null
  let family: ShowroomFamily | null = null

  if (product.parent) {
    parent = showroomData<CatalogOne<ShowroomProductModel>>(await showroom.GET('/product-models/{productModel}', { params: { path: { productModel: product.parent } } })).data

    if (parent.family_variant) {
      variant = showroomData<CatalogOne<ShowroomFamilyVariant>>(await showroom.GET('/family-variants/{familyVariant}', { params: { path: { familyVariant: parent.family_variant } } })).data
    }
  } else if (product.family) {
    family = showroomData<CatalogOne<ShowroomFamily>>(await showroom.GET('/families/{family}', { params: { path: { family: product.family } } })).data
  }

  return { product, versions, parent, variant, family }
}, productId)

const product = computed(() => data.value?.product ?? null)
const parent = computed(() => data.value?.parent ?? null)
const isVariant = computed(() => Boolean(product.value?.parent))

const families = usePickList(async showroom => showroomData<CatalogList<ShowroomFamily>>(await showroom.GET('/families', { params: { query: { per_page: ALL } } })))
const flagOptions = usePickList(async showroom => showroomData<CatalogList<ShowroomFlag>>(await showroom.GET('/flags', { params: { query: { per_page: ALL } } })))
const associationTypes = usePickList(async showroom => showroomData<CatalogList<ShowroomAssociationType>>(await showroom.GET('/association-types', { params: { query: { per_page: ALL } } })))

const productFlags = computed(() => flagOptions.value.filter(flag => flag.applies_to.includes('product')))

const inherited = computed(() => parent.value?.values ?? {})
const own = computed(() => ownValues(product.value?.values, inherited.value))
const inheritedCategories = computed(() => parent.value?.categories ?? [])

/** An attribute the user added to the form ("add attribute"), until saved. */
const added = ref<string[]>([])

/** The attributes the value form shows: a variant's settable ones, or the family's, those it has values for and those added. */
const formCodes = computed<string[]>(() => {
  const loaded = data.value

  if (!loaded) {
    return []
  }

  if (loaded.parent) {
    return levelAttributes(loaded.variant, loaded.parent.level + 1)
  }

  const codes = (loaded.family?.attributes ?? []).map(attribute => attribute.attribute)

  return [...codes, ...[...Object.keys(own.value), ...added.value].filter(code => !codes.includes(code)).sort()]
    .filter((code, index, all) => all.indexOf(code) === index)
})

const catalog = useAttributeCatalog(() => formCodes.value)
const editorAttributes = computed(() => catalog.pick(formCodes.value))
const addable = computed(() => isVariant.value ? [] : [...catalog.attributes.value.keys()].filter(code => !formCodes.value.includes(code)).sort())
const toAdd = ref<string | null>('')

const slots = useEditingSlot()

const form = reactive({ family: '' as string | null, owner: '', enabled: true, categories: '', flags: [] as string[] })

const familyItems = computed(() => [
  { label: t('showroom.catalog.products.no_family'), value: null },
  ...families.value.map(family => ({ label: labelOf(family.labels, locale.value, family.code), value: family.code }))
])

const addableItems = computed(() => addable.value.map(code => ({ label: code, value: code })))
const inputs = ref<Record<string, ValueInput>>({})
const externalIds = ref<ExternalIdsForm>(externalIdsForm(null))

function fill(): void {
  const current = product.value

  if (!current) {
    return
  }

  form.family = current.family ?? ''
  form.owner = current.owner ?? ''
  form.enabled = current.enabled
  form.categories = ownCategories(current.categories, inheritedCategories.value).join(', ')
  form.flags = [...current.flags ?? []]
  externalIds.value = externalIdsForm(current.external_ids)
  inputs.value = valueInputs(editorAttributes.value, own.value, slots.slot.value)
  added.value = []
}

watch([data, () => slots.slot.value], fill, { immediate: true })

// Attributes that appear later (loaded, or added) get their inputs without
// clearing what was typed into the others.
watch(editorAttributes, (attributes) => {
  const missing = attributes.filter(attribute => !(attribute.code in inputs.value))

  if (missing.length > 0) {
    inputs.value = { ...valueInputs(missing, own.value, slots.slot.value), ...inputs.value }
  }
})

function addAttribute(): void {
  if (toAdd.value && !added.value.includes(toAdd.value)) {
    added.value = [...added.value, toAdd.value]
  }

  toAdd.value = ''
}

function toggleFlag(code: string, on: boolean): void {
  form.flags = on ? [...form.flags.filter(flag => flag !== code), code] : form.flags.filter(flag => flag !== code)
}

function familyLabel(code: string | null | undefined): string | null {
  const family = families.value.find(entry => entry.code === code)

  return family ? labelOf(family.labels, locale.value, family.code) : code ?? null
}

async function update(body: Record<string, unknown>): Promise<unknown> {
  if (!client) {
    return null
  }

  return showroomData(await client.PATCH('/products/{product}', { params: { path: { product: productId.value } }, body: untyped(body) }))
}

async function save(): Promise<void> {
  const current = product.value

  if (!current) {
    return
  }

  const body: Record<string, unknown> = {
    ...(isVariant.value ? {} : { family: form.family || null, owner: blankToNull(form.owner) }),
    enabled: form.enabled,
    categories: splitCodes(form.categories),
    values: valuesBody(editorAttributes.value, inputs.value, slots.slot.value),
    external_ids: externalIdsBody(externalIds.value),
    ...(current.flags !== undefined ? { flags: form.flags } : {})
  }

  if (await action.run(() => update(body))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.catalog.products.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/products/{product}', { params: { path: { product: productId.value } } })))) {
    await navigateTo('/showroom/products')
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-product">
    <AtriumIntegrationScreen screen-key="showroom.product" :record="productId">
      <CatalogState :ready="!pending && product !== null" :failed="failed" :status="status">
        <div v-if="product" class="space-y-5">
          <UPageHeader :description="product.family ? familyLabel(product.family) ?? undefined : undefined">
            <template #title>
              <span class="font-mono" data-testid="showroom-product-title">{{ product.identifier }}</span>
            </template>
            <template #links>
              <UBadge :color="product.enabled ? 'success' : 'neutral'" variant="subtle" data-testid="showroom-product-enabled">
                {{ product.enabled ? t('showroom.catalog.enabled') : t('showroom.catalog.disabled') }}
              </UBadge>
              <UButton v-if="product.parent" :to="productModelPath(product.parent)" variant="outline" icon="i-lucide-arrow-left" data-testid="showroom-product-parent">{{ product.parent }}</UButton>
              <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-product-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
            </template>
          </UPageHeader>

          <ProductWorkflow :product="product" :versions="data?.versions ?? []" @changed="reload" />

          <SlotPicker :locales="slots.locales.value" :channels="slots.channels.value" :editing="slots.slot.value" @pick="slots.pick" />

          <form class="space-y-5" data-testid="showroom-product-form" @submit.prevent="save">
            <CatalogActionError :action="action" />

            <UCard>
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.products.details') }}</h2>
              </template>
              <div class="flex flex-wrap items-end gap-4">
                <UFormField v-if="!isVariant" :label="t('showroom.catalog.fields.family')" :error="action.error('family')">
                  <USelect v-model="form.family" :items="familyItems" :placeholder="t('showroom.catalog.products.no_family')" class="w-56" data-testid="showroom-product-family" />
                </UFormField>
                <div v-if="isVariant" class="text-sm" data-testid="showroom-product-inherited-owner">
                  <p class="font-medium">{{ t('showroom.catalog.products.inherited_owner') }}</p>
                  <p class="font-mono text-xs">{{ product.owner ?? t('showroom.catalog.none') }}</p>
                </div>
                <UFormField v-else :label="t('showroom.catalog.fields.owner')" :hint="t('showroom.catalog.products.owner_hint')" :error="action.error('owner')">
                  <UInput v-model="form.owner" class="w-56" data-testid="showroom-product-owner" />
                </UFormField>
                <UCheckbox v-model="form.enabled" :label="t('showroom.catalog.enabled')" class="h-9 items-center" data-testid="showroom-product-enabled-input" />
              </div>
            </UCard>

            <UCard data-testid="showroom-product-identity">
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.products.identifiers_and_flags') }}</h2>
              </template>
              <div class="flex flex-col gap-4">
                <p class="text-sm">
                  <span class="text-muted">{{ t('showroom.catalog.fields.slug') }}:</span>
                  <span class="font-mono" data-testid="showroom-product-slug">{{ product.slug }}</span>
                </p>

                <ExternalIds v-model="externalIds" :synced-at="product.external_ids_synced_at ?? {}" :error="action.error" />

                <div v-if="product.flags !== undefined" data-testid="showroom-own-flags">
                  <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.catalog.flags.own') }}</p>
                  <div class="flex flex-wrap gap-3">
                    <UCheckbox
                      v-for="flag in productFlags"
                      :key="flag.code"
                      :model-value="form.flags.includes(flag.code)"
                      :data-testid="`showroom-flag-${flag.code}`"
                      @update:model-value="toggleFlag(flag.code, $event === true)"
                    >
                      <template #label>
                        <span class="font-mono text-xs">{{ flag.code }}</span>
                        <span v-if="labelOf(flag.labels, locale, flag.code) !== flag.code" class="ml-1.5 text-muted">{{ labelOf(flag.labels, locale, flag.code) }}</span>
                      </template>
                    </UCheckbox>
                    <span v-if="productFlags.length === 0" class="text-sm text-muted">{{ t('showroom.catalog.flags.none') }}</span>
                  </div>
                  <small v-if="action.error('flags')" class="text-xs text-error">{{ action.error('flags') }}</small>
                </div>

                <div data-testid="showroom-effective-flags">
                  <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.catalog.flags.effective') }}</p>
                  <ul class="flex flex-wrap gap-3">
                    <li
                      v-for="flag in product.effective_flags ?? []"
                      :key="flag"
                      class="flex items-center gap-1.5 text-sm"
                      :data-effective-flag="flag"
                      :data-inherited="(product.flags ?? []).includes(flag) ? 'false' : 'true'"
                    >
                      <UBadge :color="(product.flags ?? []).includes(flag) ? 'primary' : 'neutral'" variant="subtle">{{ flag }}</UBadge>
                      <span v-if="!(product.flags ?? []).includes(flag)" class="text-xs text-muted">{{ t('showroom.catalog.inherited') }}</span>
                    </li>
                    <li v-if="(product.effective_flags ?? []).length === 0" class="text-sm text-muted">{{ t('showroom.catalog.none') }}</li>
                  </ul>
                </div>
              </div>
            </UCard>

            <UCard>
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.categories.title') }}</h2>
              </template>
              <div class="flex flex-col gap-2">
                <UFormField :label="t('showroom.catalog.categories.title')" :hint="t('showroom.catalog.categories.hint')" :error="action.error('categories')">
                  <UInput v-model="form.categories" class="w-full" data-testid="showroom-product-categories" />
                </UFormField>
                <p v-if="inheritedCategories.length > 0" class="text-xs" data-testid="showroom-inherited-categories">
                  {{ t('showroom.catalog.categories.inherited') }}:
                  <template v-for="(code, index) in inheritedCategories" :key="code">
                    <ULink :to="categoryPath(code)" class="font-mono hover:underline">{{ code }}</ULink><span v-if="index < inheritedCategories.length - 1">, </span>
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
              <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-product-save">{{ t('showroom.catalog.save') }}</UButton>
            </div>
          </form>

          <UCard v-if="addable.length > 0">
            <form class="flex flex-wrap items-end gap-3" data-testid="showroom-add-attribute" @submit.prevent="addAttribute">
              <UFormField :label="t('showroom.catalog.values.add_attribute')">
                <USelect v-model="toAdd" :items="addableItems" :placeholder="t('showroom.catalog.values.choose_attribute')" class="w-72" data-testid="showroom-add-attribute-select" />
              </UFormField>
              <UButton type="submit" variant="outline" icon="i-lucide-plus" data-testid="showroom-add-attribute-submit">{{ t('showroom.catalog.add') }}</UButton>
            </form>
          </UCard>

          <CatalogAssets :assets="product.assets ?? []" link-type="product" :link-target="product.identifier" @changed="reload" />

          <CatalogAssociations
            :associations="product.associations ?? {}"
            :quantified="product.quantified_associations ?? {}"
            :own-associations="ownAssociations(product.associations, parent?.associations)"
            :own-quantified="ownQuantified(product.quantified_associations, parent?.quantified_associations)"
            :types="associationTypes"
            :save="update"
            @changed="reload"
          />

          <InheritedValues :values="inherited" />
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
