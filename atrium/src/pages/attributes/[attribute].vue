<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui/components/Table.vue'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  labelOf,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  usePickList,
  type CatalogList,
  type CatalogOne,
  type ShowroomAttribute,
  type ShowroomAttributeOption
} from '../../components/catalog/catalog'
import AttributeFlags from '../../components/structure/AttributeFlags.vue'
import {
  editedLabels,
  hasOptions,
  newLabels,
  parseSettings,
  settingsText,
  sortOrder,
  type AttributeFlags as Flags,
  type ShowroomAttributeGroup
} from '../../components/structure/structure'

/**
 * `showroom.attribute`: one attribute (`attributes.show`, with its options
 * when it is a select or multiselect), ported from v1's `attributes/show`.
 * One save (`attributes.update`) sends the group, the labels (the current
 * locale's edited, the others kept), the three switches, the settings (a JSON
 * object, refused before sending otherwise) and the sort order. Options are
 * added (`attributes.options.store`) and deleted once confirmed
 * (`attributes.options.destroy`); the attribute is deleted once confirmed.
 * The code and type never change, so neither is editable.
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()
const optionAction = useCatalogAction()

const code = computed(() => String(route.params.attribute))

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogOne<ShowroomAttribute>>(await showroom.GET('/attributes/{attribute}', { params: { path: { attribute: code.value } } })).data, code)

const groups = usePickList<ShowroomAttributeGroup>(async showroom =>
  showroomData<CatalogList<ShowroomAttributeGroup>>(await showroom.GET('/attribute-groups', { params: { query: { per_page: ALL } } })))

const attribute = computed(() => data.value)
const options = computed(() => attribute.value?.options ?? [])

const form = reactive<{ group: string | null, label: string, settings: string, sort_order: string }>({ group: '', label: '', settings: '', sort_order: '0' })
const flags = ref<Flags>({ is_unique: false, is_localizable: false, is_scopable: false })
const draft = reactive({ code: '', label: '', sort_order: '0' })
const settingsError = ref<string | null>(null)

watch(data, () => {
  const current = attribute.value

  if (!current) {
    return
  }

  form.group = current.group ?? ''
  form.label = current.labels[locale.value] ?? ''
  form.settings = settingsText(current.settings)
  form.sort_order = String(current.sort_order)
  flags.value = { is_unique: current.is_unique, is_localizable: current.is_localizable, is_scopable: current.is_scopable }
}, { immediate: true })

async function save(): Promise<void> {
  const current = attribute.value
  const settings = parseSettings(form.settings)
  settingsError.value = settings === null ? t('showroom.structure.attributes.invalid_settings') : null

  if (!client || !current || settings === null) {
    return
  }

  const body = {
    group: form.group || null,
    labels: editedLabels(current.labels, locale.value, form.label),
    ...flags.value,
    settings,
    sort_order: sortOrder(form.sort_order) ?? 0
  }

  if (await action.run(async () => showroomData(await client.PATCH('/attributes/{attribute}', { params: { path: { attribute: code.value } }, body: untyped(body) })))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.structure.attributes.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/attributes/{attribute}', { params: { path: { attribute: code.value } } })))) {
    await navigateTo('/showroom/attributes')
  }
}

async function addOption(): Promise<void> {
  if (!client) {
    return
  }

  const body = { code: draft.code.trim(), ...newLabels(locale.value, draft.label), sort_order: sortOrder(draft.sort_order) ?? 0 }

  if (await optionAction.run(async () => showroomData(await client.POST('/attributes/{attribute}/options', { params: { path: { attribute: code.value } }, body: untyped(body) })))) {
    draft.code = ''
    draft.label = ''
    draft.sort_order = '0'
    await reload()
  }
}

async function deleteOption(option: string): Promise<void> {
  if (!client || !window.confirm(t('showroom.structure.attributes.confirm_delete_option', { option }))) {
    return
  }

  if (await optionAction.run(async () => showroomData(await client.DELETE('/attributes/{attribute}/options/{option}', { params: { path: { attribute: code.value, option } } })))) {
    await reload()
  }
}

const groupItems = computed(() => [
  { label: t('showroom.structure.attributes.no_group'), value: null },
  ...(form.group && !groups.value.some(group => group.code === form.group) ? [{ label: form.group, value: form.group }] : []),
  ...groups.value.map(group => ({ label: labelOf(group.labels, locale.value, group.code), value: group.code }))
])

const optionColumns = computed<TableColumn<ShowroomAttributeOption>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { accessorKey: 'sort_order', header: t('showroom.catalog.fields.sort_order'), meta: { class: { th: 'text-right', td: 'text-right' } } },
  { id: 'actions', header: '' }
])
</script>

<template>
  <div class="space-y-4" data-testid="showroom-attribute">
    <AtriumIntegrationScreen screen-key="showroom.attribute" :record="code">
      <CatalogState :ready="!pending && attribute !== null" :failed="failed" :status="status">
        <div v-if="attribute" class="space-y-5">
          <AtriumPageHeader :title="labelOf(attribute.labels, locale, attribute.code)" :description="attribute.code" data-testid="showroom-attribute-title">
            <UBadge color="neutral" variant="subtle" data-testid="showroom-attribute-type">{{ attribute.type }}</UBadge>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-attribute-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </AtriumPageHeader>

          <CatalogActionError :action="action" />

          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.structure.details') }}</h2>
            </template>
            <form class="flex max-w-xl flex-col gap-4" data-testid="showroom-attribute-form" @submit.prevent="save">
              <UFormField :label="t('showroom.catalog.fields.group')" :error="action.error('group')">
                <USelect v-model="form.group" :items="groupItems" :placeholder="t('showroom.structure.attributes.no_group')" class="w-64" data-testid="showroom-attribute-group" />
              </UFormField>

              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="form.label" class="w-64" data-testid="showroom-attribute-label" />
              </UFormField>

              <AttributeFlags v-model="flags" testid="showroom-attribute-flag" />

              <UFormField :label="t('showroom.structure.attributes.settings')" :hint="t('showroom.structure.attributes.settings_hint')" :error="settingsError ?? action.error('settings')">
                <UTextarea v-model="form.settings" :rows="4" class="w-full font-mono" data-testid="showroom-attribute-settings" />
              </UFormField>

              <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
                <UInput v-model="form.sort_order" type="number" min="0" class="w-32" data-testid="showroom-attribute-sort" />
              </UFormField>

              <div>
                <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-attribute-save">{{ t('showroom.catalog.save') }}</UButton>
              </div>
            </form>
          </UCard>

          <UCard v-if="hasOptions(attribute.type)" data-testid="showroom-attribute-options">
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.structure.attributes.options') }}</h2>
            </template>
            <div class="space-y-4">
              <UTable :data="options" :columns="optionColumns">
                <template #empty>
                  <AtriumState compact :title="t('showroom.structure.attributes.no_options')" />
                </template>
                <template #code-cell="{ row: { original: option } }">
                  <span class="font-mono text-xs" :data-testid="`showroom-option-row-${option.code}`">{{ option.code }}</span>
                </template>
                <template #label-cell="{ row: { original: option } }">{{ labelOf(option.labels, locale, option.code) }}</template>
                <template #sort_order-cell="{ row: { original: option } }"><span class="tabular-nums">{{ option.sort_order }}</span></template>
                <template #actions-cell="{ row: { original: option } }">
                  <UButton
                    variant="ghost"
                    size="sm"
                    icon="i-lucide-trash-2"
                    :aria-label="t('showroom.catalog.delete')"
                    :data-testid="`showroom-option-delete-${option.code}`"
                    @click="deleteOption(option.code)"
                  />
                </template>
              </UTable>

              <CatalogActionError :action="optionAction" />

              <form class="flex flex-wrap items-end gap-3" data-testid="showroom-option-create-form" @submit.prevent="addOption">
                <UFormField :label="t('showroom.catalog.fields.code')" :error="optionAction.error('code')" required>
                  <UInput v-model="draft.code" required class="w-48" data-testid="showroom-option-create-code" />
                </UFormField>
                <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="optionAction.error('labels')">
                  <UInput v-model="draft.label" class="w-56" data-testid="showroom-option-create-label" />
                </UFormField>
                <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="optionAction.error('sort_order')">
                  <UInput v-model="draft.sort_order" type="number" min="0" class="w-28" data-testid="showroom-option-create-sort" />
                </UFormField>
                <UButton type="submit" icon="i-lucide-plus" :loading="optionAction.busy.value" data-testid="showroom-option-create">{{ t('showroom.structure.attributes.add_option') }}</UButton>
              </form>
            </div>
          </UCard>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
