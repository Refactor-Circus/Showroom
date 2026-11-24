<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  categoryPath,
  labelOf,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogOne
} from '../../components/catalog/catalog'
import CategoryBranch from '../../components/structure/CategoryBranch.vue'
import {
  branchOf,
  categoryProductsPath,
  editedLabels,
  loadBranch,
  loadChain,
  newLabels,
  sortOrder,
  type ShowroomCategory
} from '../../components/structure/structure'

/**
 * `showroom.category`: one category (`categories.show`), ported from v1's
 * `categories/show`. Its chain from the tree root is loaded one ancestor at
 * a time (`categories.show` names only the parent), and its whole branch
 * comes from `categories.index?under=`, every page, drawn as a tree. A child
 * is added under it (`categories.store` with `parent`). A save
 * (`categories.update`) sends the labels, the sort order and the parent:
 * a new parent moves the branch, and an empty one makes it a tree of its
 * own. Delete is confirmed.
 */
interface Loaded {
  category: ShowroomCategory
  chain: ShowroomCategory[]
  branch: ShowroomCategory[]
}

const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()
const childAction = useCatalogAction()

const code = computed(() => String(route.params.category))

const { client, data, pending, failed, status, reload } = useCatalogLoad<Loaded>(async (showroom) => {
  const category = showroomData<CatalogOne<ShowroomCategory>>(await showroom.GET('/categories/{category}', { params: { path: { category: code.value } } })).data
  const [chain, branch] = await Promise.all([loadChain(showroom, category), loadBranch(showroom, category.code)])

  return { category, chain, branch }
}, code)

const category = computed(() => data.value?.category ?? null)
const ancestors = computed(() => data.value?.chain.slice(0, -1) ?? [])
const tree = computed(() => branchOf(code.value, data.value?.branch ?? []))

const form = reactive({ label: '', sort_order: '0', parent: '' })
const draft = reactive({ code: '', label: '', sort_order: '0' })

watch(data, () => {
  const current = category.value

  if (current) {
    form.label = current.labels[locale.value] ?? ''
    form.sort_order = String(current.sort_order)
    form.parent = current.parent ?? ''
  }
}, { immediate: true })

async function save(): Promise<void> {
  const current = category.value

  if (!client || !current) {
    return
  }

  const body = {
    labels: editedLabels(current.labels, locale.value, form.label),
    sort_order: sortOrder(form.sort_order) ?? 0,
    parent: form.parent.trim() || null
  }

  if (await action.run(async () => showroomData(await client.PATCH('/categories/{category}', { params: { path: { category: code.value } }, body: untyped(body) })))) {
    await reload()
  }
}

async function addChild(): Promise<void> {
  if (!client) {
    return
  }

  const body = { code: draft.code.trim(), parent: code.value, ...newLabels(locale.value, draft.label), sort_order: sortOrder(draft.sort_order) ?? 0 }

  if (await childAction.run(async () => showroomData(await client.POST('/categories', { body: untyped(body) })))) {
    draft.code = ''
    draft.label = ''
    draft.sort_order = '0'
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.structure.categories.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/categories/{category}', { params: { path: { category: code.value } } })))) {
    await navigateTo('/showroom/categories')
  }
}

const chain = computed(() => category.value === null ? [] : [
  { label: t('showroom.structure.categories.title'), to: '/showroom/categories' },
  ...ancestors.value.map(link => ({ label: labelOf(link.labels, locale.value, link.code), to: categoryPath(link.code) })),
  { label: labelOf(category.value.labels, locale.value, category.value.code) }
])
</script>

<template>
  <div class="space-y-4" data-testid="showroom-category">
    <AtriumIntegrationScreen screen-key="showroom.category" :record="code">
      <CatalogState :ready="!pending && category !== null" :failed="failed" :status="status">
        <div v-if="category" class="space-y-5">
          <AtriumPageHeader :title="labelOf(category.labels, locale, category.code)" :description="category.code" data-testid="showroom-category-title">
            <UButton :to="categoryProductsPath(category.code)" variant="outline" icon="i-lucide-box" data-testid="showroom-category-products">{{ t('showroom.structure.categories.products') }}</UButton>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-category-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </AtriumPageHeader>

          <UBreadcrumb :items="chain" :aria-label="t('showroom.structure.categories.chain')" data-testid="showroom-category-chain" />

          <CatalogActionError :action="action" />

          <UCard data-testid="showroom-category-branch">
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.structure.categories.branch') }}</h2>
            </template>
            <div class="space-y-4">
              <AtriumState v-if="tree.length === 0" compact :title="t('showroom.structure.categories.no_branch')" data-testid="showroom-branch-empty" />
              <CategoryBranch v-else :nodes="tree" />

              <CatalogActionError :action="childAction" />

              <form class="flex flex-wrap items-end gap-3" data-testid="showroom-child-create-form" @submit.prevent="addChild">
                <UFormField :label="t('showroom.catalog.fields.code')" :error="childAction.error('code')" required>
                  <UInput v-model="draft.code" required class="w-48" data-testid="showroom-child-create-code" />
                </UFormField>
                <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="childAction.error('labels')">
                  <UInput v-model="draft.label" class="w-56" data-testid="showroom-child-create-label" />
                </UFormField>
                <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="childAction.error('sort_order')">
                  <UInput v-model="draft.sort_order" type="number" min="0" class="w-28" data-testid="showroom-child-create-sort" />
                </UFormField>
                <UButton type="submit" icon="i-lucide-plus" :loading="childAction.busy.value" data-testid="showroom-child-create">{{ t('showroom.structure.categories.add_child') }}</UButton>
              </form>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.structure.details') }}</h2>
            </template>
            <form class="flex flex-wrap items-end gap-3" data-testid="showroom-category-form" @submit.prevent="save">
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="form.label" class="w-64" data-testid="showroom-category-label" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
                <UInput v-model="form.sort_order" type="number" min="0" class="w-28" data-testid="showroom-category-sort" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.parent')" :hint="t('showroom.structure.categories.move_hint')" :error="action.error('parent')">
                <UInput v-model="form.parent" class="w-56" data-testid="showroom-category-parent" />
              </UFormField>
              <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-category-save">{{ t('showroom.catalog.save') }}</UButton>
            </form>
          </UCard>

          <p class="text-sm text-muted" data-testid="showroom-category-counts">
            {{ t('showroom.structure.categories.filed_counts', { products: category.products_count ?? 0, models: category.product_models_count ?? 0 }) }}
          </p>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
