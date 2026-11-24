<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui/components/Table.vue'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  attributePath,
  blankToNull,
  familyVariantPath,
  labelOf,
  showroomData,
  splitCodes,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogList,
  type CatalogOne,
  type ShowroomFamily,
  type ShowroomFamilyVariant
} from '../../components/catalog/catalog'
import { useAttributeCatalog } from '../../components/catalog/values'

/**
 * `showroom.family`: one family (`families.show`) and its variants
 * (`family-variants.index?family=`), ported from v1's `families/show`. One
 * save (`families.update`) sends the label in the current locale, the label
 * attribute, the sort order and the whole membership, since Showroom
 * replaces it: every row kept (its required flag, required channels and
 * order), plus one attribute to add. A new family variant
 * (`family-variants.store`) takes two levels of axes and attributes; the
 * second is left out when empty. Delete is confirmed.
 */
interface Loaded {
  family: ShowroomFamily
  variants: ShowroomFamilyVariant[]
}

interface Row {
  attribute: string
  type: string
  group: string | null
  is_required: boolean
  required_channels: string
  sort_order: string | number
  remove: boolean
}

interface LevelPick {
  axes: string[]
  attributes: string[]
}

const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()
const variantAction = useCatalogAction()

const code = computed(() => String(route.params.family))

const { client, data, pending, failed, status, reload } = useCatalogLoad<Loaded>(async (showroom) => {
  const family = showroomData<CatalogOne<ShowroomFamily>>(await showroom.GET('/families/{family}', { params: { path: { family: code.value } } })).data
  const variants = showroomData<CatalogList<ShowroomFamilyVariant>>(await showroom.GET('/family-variants', { params: { query: { family: code.value, per_page: ALL } } })).data

  return { family, variants }
}, code)

const family = computed(() => data.value?.family ?? null)
const members = computed(() => family.value?.attributes ?? [])

const catalog = useAttributeCatalog(() => [])
const available = computed(() => [...catalog.attributes.value.values()]
  .filter(attribute => !members.value.some(member => member.attribute === attribute.code))
  .sort((a, b) => a.code.localeCompare(b.code)))
const labelCandidates = computed(() => members.value.filter(member => member.type === 'text'))

const form = reactive({ label: '', label_attribute: '' as string | null, sort_order: '0' as string | number, add_attribute: '' as string | null, add_required: false })

const labelAttributeItems = computed(() => [
  { label: t('showroom.catalog.families.no_label_attribute'), value: null },
  ...labelCandidates.value.map(member => ({ label: member.attribute, value: member.attribute }))
])

const availableItems = computed(() => [
  { label: t('showroom.catalog.values.choose_attribute'), value: null },
  ...available.value.map(attribute => ({ label: `${attribute.code} (${attribute.type})`, value: attribute.code }))
])

const memberColumns = computed<TableColumn<Row>[]>(() => [
  { accessorKey: 'attribute', header: t('showroom.catalog.fields.code') },
  { accessorKey: 'type', header: t('showroom.catalog.fields.type') },
  { accessorKey: 'group', header: t('showroom.catalog.fields.group') },
  { accessorKey: 'is_required', header: t('showroom.catalog.families.required') },
  { accessorKey: 'required_channels', header: t('showroom.catalog.families.required_channels') },
  { accessorKey: 'sort_order', header: t('showroom.catalog.fields.sort_order') },
  { accessorKey: 'remove', header: t('showroom.catalog.remove') }
])
const rows = ref<Row[]>([])
const draft = reactive({ code: '', levels: [{ axes: [], attributes: [] }, { axes: [], attributes: [] }] as LevelPick[] })

watch(data, () => {
  const current = family.value

  if (!current) {
    return
  }

  form.label = current.labels[locale.value] ?? ''
  form.label_attribute = current.label_attribute ?? ''
  form.sort_order = String(current.sort_order)
  form.add_attribute = ''
  form.add_required = false
  rows.value = (current.attributes ?? []).map(member => ({
    attribute: member.attribute,
    type: member.type,
    group: member.group ?? null,
    is_required: member.is_required,
    required_channels: (member.required_channels ?? []).join(', '),
    sort_order: String(member.sort_order),
    remove: false
  }))
}, { immediate: true })

async function save(): Promise<void> {
  const current = family.value

  if (!client || !current) {
    return
  }

  const kept = rows.value.filter(row => !row.remove).map((row) => {
    const channels = splitCodes(row.required_channels)

    return { attribute: row.attribute, is_required: row.is_required, required_channels: channels.length > 0 ? channels : null, sort_order: Number(row.sort_order) || 0 }
  })

  const attributes = form.add_attribute
    ? [...kept, { attribute: form.add_attribute, is_required: form.add_required, sort_order: kept.length }]
    : kept

  const body = {
    labels: { ...current.labels, [locale.value]: blankToNull(form.label) },
    label_attribute: form.label_attribute || null,
    sort_order: Number(form.sort_order) || 0,
    attributes
  }

  if (await action.run(async () => showroomData(await client.PATCH('/families/{family}', { params: { path: { family: code.value } }, body: untyped(body) })))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.catalog.families.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/families/{family}', { params: { path: { family: code.value } } })))) {
    await navigateTo('/showroom/families')
  }
}

function togglePick(level: LevelPick, kind: keyof LevelPick, attribute: string, on: boolean): void {
  level[kind] = on ? [...level[kind].filter(entry => entry !== attribute), attribute] : level[kind].filter(entry => entry !== attribute)
}

async function createVariant(): Promise<void> {
  if (!client) {
    return
  }

  const levels = draft.levels
    .filter((level, index) => index === 0 || level.axes.length > 0 || level.attributes.length > 0)
    .map(level => ({ axes: [...level.axes], attributes: [...level.attributes] }))

  if (await variantAction.run(async () => showroomData(await client.POST('/family-variants', {
    body: untyped({ code: draft.code.trim(), family: code.value, levels })
  })))) {
    draft.code = ''
    draft.levels = [{ axes: [], attributes: [] }, { axes: [], attributes: [] }]
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-family">
    <AtriumIntegrationScreen screen-key="showroom.family" :record="code">
      <CatalogState :ready="!pending && family !== null" :failed="failed" :status="status">
        <div v-if="family" class="space-y-5">
          <UPageHeader :headline="t('showroom.catalog.fields.family')">
            <template #title>
              <span data-testid="showroom-family-title">{{ labelOf(family.labels, locale, family.code) }}</span>
            </template>
            <template #description>
              <span class="font-mono">{{ family.code }}</span>
            </template>
            <template #links>
              <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-family-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
            </template>
          </UPageHeader>

          <form class="space-y-5" data-testid="showroom-family-form" @submit.prevent="save">
            <CatalogActionError :action="action" />

            <UCard>
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.products.details') }}</h2>
              </template>
              <div class="flex flex-wrap items-end gap-3">
                <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                  <UInput v-model="form.label" class="w-64" data-testid="showroom-family-label" />
                </UFormField>
                <UFormField :label="t('showroom.catalog.families.label_attribute')" :hint="t('showroom.catalog.families.label_attribute_hint')" :error="action.error('label_attribute')">
                  <USelect v-model="form.label_attribute" :items="labelAttributeItems" :placeholder="t('showroom.catalog.families.no_label_attribute')" class="w-64" data-testid="showroom-family-label-attribute" />
                </UFormField>
                <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
                  <UInput v-model="form.sort_order" type="number" min="0" class="w-28" data-testid="showroom-family-sort" />
                </UFormField>
              </div>
            </UCard>

            <UCard data-testid="showroom-family-attributes">
              <template #header>
                <h2 class="font-semibold">{{ t('showroom.catalog.families.members') }}</h2>
              </template>
              <div class="space-y-4">
                <p v-if="rows.length === 0" class="text-sm text-muted">{{ t('showroom.catalog.families.no_attributes') }}</p>
                <UTable v-else :data="rows" :columns="memberColumns">
                  <template #attribute-cell="{ row: { original: member } }">
                    <span :data-testid="`showroom-family-attribute-${member.attribute}`">
                      <ULink :to="attributePath(member.attribute)" class="font-mono hover:underline">{{ member.attribute }}</ULink>
                    </span>
                  </template>
                  <template #type-cell="{ row: { original: member } }"><UBadge color="neutral" variant="subtle">{{ member.type }}</UBadge></template>
                  <template #group-cell="{ row: { original: member } }">{{ member.group ?? t('showroom.catalog.none') }}</template>
                  <template #is_required-cell="{ row: { original: member } }">
                    <UCheckbox v-model="member.is_required" :aria-label="t('showroom.catalog.families.required')" :data-testid="`showroom-family-required-${member.attribute}`" />
                  </template>
                  <template #required_channels-cell="{ row: { original: member } }">
                    <UInput
                      v-model="member.required_channels"
                      size="sm"
                      class="w-40"
                      :placeholder="t('showroom.catalog.families.required_channels_hint')"
                      :data-testid="`showroom-family-channels-${member.attribute}`"
                    />
                  </template>
                  <template #sort_order-cell="{ row: { original: member } }">
                    <UInput v-model="member.sort_order" type="number" min="0" size="sm" class="w-20" :data-testid="`showroom-family-sort-${member.attribute}`" />
                  </template>
                  <template #remove-cell="{ row: { original: member } }">
                    <UCheckbox v-model="member.remove" :aria-label="t('showroom.catalog.remove')" :data-testid="`showroom-family-remove-${member.attribute}`" />
                  </template>
                </UTable>
                <small v-if="action.error('attributes')" class="text-xs text-error">{{ action.error('attributes') }}</small>

                <div class="flex flex-wrap items-end gap-3">
                  <UFormField :label="t('showroom.catalog.values.add_attribute')">
                    <USelect v-model="form.add_attribute" :items="availableItems" :placeholder="t('showroom.catalog.values.choose_attribute')" class="w-72" data-testid="showroom-family-add-attribute" />
                  </UFormField>
                  <UCheckbox v-model="form.add_required" :label="t('showroom.catalog.families.required')" class="h-9 items-center" data-testid="showroom-family-add-required" />
                </div>
              </div>
            </UCard>

            <div>
              <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-family-save">{{ t('showroom.catalog.save') }}</UButton>
            </div>
          </form>

          <UCard data-testid="showroom-family-variants">
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.catalog.families.variants') }}</h2>
            </template>
            <div class="space-y-4">
              <ul v-if="(data?.variants ?? []).length > 0" class="flex flex-wrap gap-2">
                <li v-for="variant in data?.variants ?? []" :key="variant.code">
                  <ULink :to="familyVariantPath(variant.code)" class="font-mono text-sm hover:underline" :data-testid="`showroom-family-variant-${variant.code}`">{{ variant.code }}</ULink>
                </li>
              </ul>

              <CatalogActionError :action="variantAction" />

              <form class="flex flex-col gap-4" data-testid="showroom-family-variant-create-form" @submit.prevent="createVariant">
                <UFormField :label="t('showroom.catalog.fields.code')" :error="variantAction.error('code')" required>
                  <UInput v-model="draft.code" required class="w-64" data-testid="showroom-family-variant-code" />
                </UFormField>

                <fieldset v-for="(level, index) in draft.levels" :key="index" class="rounded-md border border-default p-3">
                  <legend class="px-1 text-sm font-medium">{{ t('showroom.catalog.families.level', { level: index + 1 }) }}</legend>
                  <div class="grid gap-3 sm:grid-cols-2">
                    <div v-for="kind in (['axes', 'attributes'] as const)" :key="kind">
                      <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t(`showroom.catalog.families.${kind}`) }}</p>
                      <div class="flex flex-wrap gap-3">
                        <UCheckbox
                          v-for="member in members"
                          :key="member.attribute"
                          :model-value="level[kind].includes(member.attribute)"
                          :data-testid="`showroom-level-${index}-${kind}-${member.attribute}`"
                          @update:model-value="togglePick(level, kind, member.attribute, $event === true)"
                        >
                          <template #label>
                            <span class="font-mono text-xs">{{ member.attribute }}</span>
                          </template>
                        </UCheckbox>
                      </div>
                    </div>
                  </div>
                  <small v-if="variantAction.error(`levels.${index}`)" class="text-xs text-error">{{ variantAction.error(`levels.${index}`) }}</small>
                </fieldset>

                <div>
                  <UButton type="submit" icon="i-lucide-plus" :loading="variantAction.busy.value" data-testid="showroom-family-variant-create">{{ t('showroom.catalog.families.create_variant') }}</UButton>
                </div>
              </form>
            </div>
          </UCard>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
