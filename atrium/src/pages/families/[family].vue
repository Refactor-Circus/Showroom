<script setup lang="ts">
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
  sort_order: string
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

const form = reactive({ label: '', label_attribute: '', sort_order: '0', add_attribute: '', add_required: false })
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

function checkedOf(event: Event): boolean {
  return (event.target as HTMLInputElement).checked
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
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h1 class="text-xl font-semibold" data-testid="showroom-family-title">{{ labelOf(family.labels, locale, family.code) }}</h1>
              <p class="font-mono text-sm text-muted">{{ family.code }}</p>
            </div>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-family-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </div>

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
                  <select v-model="form.label_attribute" class="w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-family-label-attribute">
                    <option value="">{{ t('showroom.catalog.families.no_label_attribute') }}</option>
                    <option v-for="member in labelCandidates" :key="member.attribute" :value="member.attribute">{{ member.attribute }}</option>
                  </select>
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
                <table v-else class="w-full text-left text-sm">
                  <thead class="text-muted">
                    <tr>
                      <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                      <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.fields.type') }}</th>
                      <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.fields.group') }}</th>
                      <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.families.required') }}</th>
                      <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.families.required_channels') }}</th>
                      <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.fields.sort_order') }}</th>
                      <th class="py-1 font-medium">{{ t('showroom.catalog.remove') }}</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-default">
                    <tr v-for="row in rows" :key="row.attribute" :data-testid="`showroom-family-attribute-${row.attribute}`">
                      <td class="py-1 pr-3"><RouterLink :to="attributePath(row.attribute)" class="font-mono hover:underline">{{ row.attribute }}</RouterLink></td>
                      <td class="py-1 pr-3"><UBadge color="neutral" variant="subtle">{{ row.type }}</UBadge></td>
                      <td class="py-1 pr-3">{{ row.group ?? t('showroom.catalog.none') }}</td>
                      <td class="py-1 pr-3"><input v-model="row.is_required" type="checkbox" :data-testid="`showroom-family-required-${row.attribute}`"></td>
                      <td class="py-1 pr-3">
                        <input
                          v-model="row.required_channels"
                          class="w-40 rounded-md border border-default bg-default px-2 py-1 text-sm"
                          :placeholder="t('showroom.catalog.families.required_channels_hint')"
                          :data-testid="`showroom-family-channels-${row.attribute}`"
                        >
                      </td>
                      <td class="py-1 pr-3">
                        <input v-model="row.sort_order" type="number" min="0" class="w-20 rounded-md border border-default bg-default px-2 py-1 text-sm" :data-testid="`showroom-family-sort-${row.attribute}`">
                      </td>
                      <td class="py-1"><input v-model="row.remove" type="checkbox" :data-testid="`showroom-family-remove-${row.attribute}`"></td>
                    </tr>
                  </tbody>
                </table>
                <small v-if="action.error('attributes')" class="text-xs text-error">{{ action.error('attributes') }}</small>

                <div class="flex flex-wrap items-end gap-3">
                  <UFormField :label="t('showroom.catalog.values.add_attribute')">
                    <select v-model="form.add_attribute" class="w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-family-add-attribute">
                      <option value="">{{ t('showroom.catalog.values.choose_attribute') }}</option>
                      <option v-for="attribute in available" :key="attribute.code" :value="attribute.code">{{ attribute.code }} ({{ attribute.type }})</option>
                    </select>
                  </UFormField>
                  <label class="flex h-9 items-center gap-2 text-sm">
                    <input v-model="form.add_required" type="checkbox" data-testid="showroom-family-add-required">
                    {{ t('showroom.catalog.families.required') }}
                  </label>
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
                  <RouterLink :to="familyVariantPath(variant.code)" class="font-mono text-sm hover:underline" :data-testid="`showroom-family-variant-${variant.code}`">{{ variant.code }}</RouterLink>
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
                        <label v-for="member in members" :key="member.attribute" class="flex items-center gap-1.5 text-sm">
                          <input
                            type="checkbox"
                            :checked="level[kind].includes(member.attribute)"
                            :data-testid="`showroom-level-${index}-${kind}-${member.attribute}`"
                            @change="togglePick(level, kind, member.attribute, checkedOf($event))"
                          >
                          <span class="font-mono text-xs">{{ member.attribute }}</span>
                        </label>
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
