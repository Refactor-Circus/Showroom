<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import ExternalIds from '../../components/catalog/ExternalIds.vue'
import {
  ALL,
  blankToNull,
  externalIdsBody,
  externalIdsForm,
  labelOf,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  usePickList,
  type CatalogList,
  type CatalogOne,
  type ExternalIdsForm,
  type ShowroomFlag
} from '../../components/catalog/catalog'
import { editedLabels } from '../../components/structure/structure'
import OwnerAssets from '../../components/owners/OwnerAssets.vue'
import { ownerSearchPath, type ShowroomOwner, type ShowroomOwnerType } from '../../components/owners/owners'

/**
 * `showroom.owner`: one owner (`owners.show`), ported from v1's
 * `owners/show`: its chain from the root, its children, linked assets and
 * the products it owns. A save (`owners.update`) sends the labels (the
 * current locale's edited, the others kept), the parent code (blank makes it
 * a root), enabled, its external ids and own flags. A child is added under it
 * (`owners.store` with it as parent), an asset is uploaded and linked to it,
 * and delete is confirmed (`owners.destroy`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()
const childAction = useCatalogAction()

const ownerId = computed(() => String(route.params.owner))

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogOne<ShowroomOwner>>(await showroom.GET('/owners/{owner}', { params: { path: { owner: ownerId.value } } })).data, ownerId)

const owner = computed(() => data.value)
const ancestors = computed(() => (owner.value?.chain ?? []).slice(0, -1))

const types = usePickList(async showroom => showroomData<CatalogList<ShowroomOwnerType>>(await showroom.GET('/owner-types', { params: { query: { per_page: ALL } } })))
const flagOptions = usePickList(async showroom => showroomData<CatalogList<ShowroomFlag>>(await showroom.GET('/flags', { params: { query: { per_page: ALL } } })))

const ownerFlags = computed(() => flagOptions.value.filter(flag => flag.applies_to.includes('owner')))

/** The types a child of this owner may have: those allowing its type as parent. */
const childTypeItems = computed(() => childTypes.value.map(type => ({ label: typeLabel(type.code), value: type.code })))

const childTypes = computed(() => types.value.filter(type => type.parent_types === null || type.parent_types === undefined || type.parent_types.includes(owner.value?.type ?? '')))

const form = reactive({ label: '', parent: '', enabled: true, flags: [] as string[] })
const externalIds = ref<ExternalIdsForm>(externalIdsForm(null))
const child = reactive({ code: '', type: '' })

watch(data, () => {
  const current = owner.value

  if (!current) {
    return
  }

  form.label = current.labels[locale.value] ?? ''
  form.parent = current.parent ?? ''
  form.enabled = current.enabled
  form.flags = [...current.flags ?? []]
  externalIds.value = externalIdsForm(current.external_ids)
}, { immediate: true })

function toggleFlag(code: string, on: boolean): void {
  form.flags = on ? [...form.flags.filter(flag => flag !== code), code] : form.flags.filter(flag => flag !== code)
}

function typeLabel(code: string | undefined): string {
  const type = types.value.find(entry => entry.code === code)

  return type ? labelOf(type.labels, locale.value, type.code) : code ?? ''
}

async function save(): Promise<void> {
  const current = owner.value

  if (!client || !current) {
    return
  }

  const body = {
    labels: editedLabels(current.labels, locale.value, form.label),
    parent: blankToNull(form.parent),
    enabled: form.enabled,
    external_ids: externalIdsBody(externalIds.value),
    ...(current.flags !== undefined ? { flags: form.flags } : {})
  }

  if (await action.run(async () => showroomData(await client.PATCH('/owners/{owner}', { params: { path: { owner: ownerId.value } }, body: untyped(body) })))) {
    await reload()
  }
}

async function addChild(): Promise<void> {
  const current = owner.value

  if (!client || !current) {
    return
  }

  const body = { code: child.code.trim(), type: child.type, parent: current.code }

  if (await childAction.run(async () => showroomData(await client.POST('/owners', { body: untyped(body) })))) {
    child.code = ''
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.owners.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/owners/{owner}', { params: { path: { owner: ownerId.value } } })))) {
    await navigateTo('/showroom/owners')
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-owner">
    <AtriumIntegrationScreen screen-key="showroom.owner" :record="ownerId">
      <CatalogState :ready="!pending && owner !== null" :failed="failed" :status="status">
        <div v-if="owner" class="space-y-5">
          <nav v-if="(owner.chain ?? []).length > 1" class="flex flex-wrap items-center gap-1 text-sm text-muted" data-testid="showroom-owner-chain">
            <template v-for="code in ancestors" :key="code">
              <ULink :to="ownerSearchPath(code)" class="font-mono hover:underline">{{ code }}</ULink>
              <UIcon name="i-lucide-chevron-right" class="size-3" />
            </template>
            <span class="font-mono text-default">{{ owner.code }}</span>
          </nav>

          <AtriumPageHeader :overline="t('showroom.owners.title')">
            <template #title>
              <span class="truncate" data-testid="showroom-owner-title">{{ labelOf(owner.labels, locale, owner.code) }}</span>
            </template>
            <template #description>
              <span class="font-mono text-sm" data-testid="showroom-owner-subtitle">{{ [owner.code, owner.type].filter(part => part).join(' · ') }}</span>
            </template>
            <UBadge :color="owner.enabled ? 'success' : 'neutral'" variant="subtle" data-testid="showroom-owner-enabled">
              {{ owner.enabled ? t('showroom.catalog.enabled') : t('showroom.catalog.disabled') }}
            </UBadge>
            <UButton
              :to="`/showroom/products?owner=${encodeURIComponent(owner.code)}`"
              variant="outline"
              icon="i-lucide-package"
              data-testid="showroom-owner-products"
            >
              {{ t('showroom.owners.products') }}
            </UButton>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-owner-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </AtriumPageHeader>

          <p class="text-sm text-muted" data-testid="showroom-owner-counts">
            {{ t('showroom.owners.counts', { products: owner.products_count ?? 0, models: owner.product_models_count ?? 0 }) }}
          </p>

          <form class="space-y-5" data-testid="showroom-owner-form" @submit.prevent="save">
            <CatalogActionError :action="action" />

            <UCard>
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.owners.details') }}</h2>
              </template>
              <div class="flex flex-wrap items-end gap-4">
                <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                  <UInput v-model="form.label" class="w-56" data-testid="showroom-owner-label" />
                </UFormField>
                <UFormField :label="t('showroom.catalog.fields.parent')" :hint="t('showroom.owners.move_hint')" :error="action.error('parent')">
                  <UInput v-model="form.parent" class="w-48" data-testid="showroom-owner-parent" />
                </UFormField>
                <UCheckbox v-model="form.enabled" :label="t('showroom.catalog.enabled')" class="h-9 items-center" data-testid="showroom-owner-enabled-input" />
              </div>
            </UCard>

            <UCard data-testid="showroom-owner-identity">
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.products.identifiers_and_flags') }}</h2>
              </template>
              <div class="flex flex-col gap-4">
                <p class="text-sm">
                  <span class="text-muted">{{ t('showroom.catalog.fields.slug') }}:</span>
                  <span class="font-mono" data-testid="showroom-owner-slug">{{ owner.slug }}</span>
                </p>

                <ExternalIds v-model="externalIds" :synced-at="owner.external_ids_synced_at ?? {}" :error="action.error" />

                <div v-if="owner.flags !== undefined" data-testid="showroom-own-flags">
                  <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.catalog.flags.own') }}</p>
                  <div class="flex flex-wrap gap-3">
                    <UCheckbox
                      v-for="flag in ownerFlags"
                      :key="flag.code"
                      :model-value="form.flags.includes(flag.code)"
                      :data-testid="`showroom-flag-${flag.code}`"
                      @update:model-value="toggleFlag(flag.code, $event === true)"
                    >
                      <template #label>
                        <span class="font-mono text-xs">{{ flag.code }}</span>
                        <span v-if="labelOf(flag.labels, locale, flag.code) !== flag.code" class="ms-1.5 text-muted">{{ labelOf(flag.labels, locale, flag.code) }}</span>
                      </template>
                    </UCheckbox>
                    <span v-if="ownerFlags.length === 0" class="text-sm text-muted">{{ t('showroom.owners.no_flags') }}</span>
                  </div>
                  <small v-if="action.error('flags')" class="text-xs text-error">{{ action.error('flags') }}</small>
                </div>

                <div data-testid="showroom-effective-flags">
                  <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.catalog.flags.effective') }}</p>
                  <ul class="flex flex-wrap gap-3">
                    <li v-for="flag in owner.effective_flags ?? []" :key="flag" class="flex items-center gap-1.5 text-sm" :data-effective-flag="flag">
                      <UBadge :color="(owner.flags ?? []).includes(flag) ? 'primary' : 'neutral'" variant="subtle">{{ flag }}</UBadge>
                      <span v-if="!(owner.flags ?? []).includes(flag)" class="text-xs text-muted">{{ t('showroom.catalog.inherited') }}</span>
                    </li>
                    <li v-if="(owner.effective_flags ?? []).length === 0" class="text-sm text-muted">{{ t('showroom.catalog.none') }}</li>
                  </ul>
                </div>
              </div>
            </UCard>

            <div>
              <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-owner-save">{{ t('showroom.catalog.save') }}</UButton>
            </div>
          </form>

          <div class="grid gap-5 lg:grid-cols-2">
            <UCard data-testid="showroom-owner-children">
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.owners.children') }}</h2>
              </template>
              <div class="space-y-4">
                <p v-if="(owner.children ?? []).length === 0" class="text-sm text-muted">{{ t('showroom.owners.no_children') }}</p>
                <ul v-else class="flex flex-wrap gap-2">
                  <li v-for="code in owner.children" :key="code">
                    <ULink :to="ownerSearchPath(code)" class="font-mono text-sm hover:underline" :data-testid="`showroom-owner-child-${code}`">{{ code }}</ULink>
                  </li>
                </ul>

                <form class="flex flex-wrap items-end gap-3" data-testid="showroom-owner-child-form" @submit.prevent="addChild">
                  <CatalogActionError :action="childAction" class="w-full" />
                  <UFormField :label="t('showroom.catalog.fields.code')" :error="childAction.error('code')" required>
                    <UInput v-model="child.code" required class="w-40" data-testid="showroom-owner-child-code" />
                  </UFormField>
                  <UFormField :label="t('showroom.catalog.fields.type')" :error="childAction.error('type')" required>
                    <USelect v-model="child.type" :items="childTypeItems" :placeholder="t('showroom.owners.choose_type')" required class="w-40" data-testid="showroom-owner-child-type" />
                  </UFormField>
                  <UButton type="submit" icon="i-lucide-plus" :loading="childAction.busy.value" data-testid="showroom-owner-child-submit">{{ t('showroom.owners.add_child') }}</UButton>
                </form>
                <small v-if="childAction.error('parent')" class="text-xs text-error">{{ childAction.error('parent') }}</small>
              </div>
            </UCard>

            <OwnerAssets :assets="owner.assets ?? []" :owner="owner.code" @changed="reload" />
          </div>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
