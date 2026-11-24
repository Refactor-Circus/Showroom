<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import {
  ALL,
  attributePath,
  labelOf,
  showroomData,
  untyped,
  useCatalogAction,
  usePickList,
  useShowroom,
  type CatalogList,
  type CatalogOne,
  type ShowroomAttribute
} from '../../components/catalog/catalog'
import AttributeFlags from '../../components/structure/AttributeFlags.vue'
import {
  ATTRIBUTE_TYPES,
  newLabels,
  parseSettings,
  sortOrder,
  type AttributeFlags as Flags,
  type ShowroomAttributeGroup
} from '../../components/structure/structure'

/**
 * `showroom.attribute-create`: v1's new-attribute form (`attributes/create`).
 * It sends the code, type, group, the label in the current locale, the three
 * switches, the settings and the sort order to `attributes.store`, then opens
 * the new attribute. Settings that are not a JSON object are refused before
 * sending, as v1's form refused them.
 */
const { t, locale } = useI18n()
const client = useShowroom()
const action = useCatalogAction()

const groups = usePickList<ShowroomAttributeGroup>(async showroom =>
  showroomData<CatalogList<ShowroomAttributeGroup>>(await showroom.GET('/attribute-groups', { params: { query: { per_page: ALL } } })))

const form = reactive<{ code: string, type: string | null, group: string | null, label: string, settings: string, sort_order: string }>({ code: '', type: '', group: '', label: '', settings: '', sort_order: '0' })

const typeItems = computed(() => ATTRIBUTE_TYPES.map(type => ({ label: type, value: type })))

const groupItems = computed(() => groups.value.map(group => ({ label: labelOf(group.labels, locale.value, group.code), value: group.code })))
const flags = ref<Flags>({ is_unique: false, is_localizable: false, is_scopable: false })
const settingsError = ref<string | null>(null)

async function create(): Promise<void> {
  const settings = parseSettings(form.settings)
  settingsError.value = settings === null ? t('showroom.structure.attributes.invalid_settings') : null

  if (!client || settings === null) {
    return
  }

  const body = {
    code: form.code.trim(),
    type: form.type ?? '',
    group: form.group || null,
    ...newLabels(locale.value, form.label),
    ...flags.value,
    settings,
    sort_order: sortOrder(form.sort_order) ?? 0
  }

  const created: { attribute?: ShowroomAttribute } = {}

  const done = await action.run(async () => {
    created.attribute = showroomData<CatalogOne<ShowroomAttribute>>(await client.POST('/attributes', { body: untyped(body) })).data
  })

  if (done && created.attribute) {
    await navigateTo(attributePath(created.attribute.code))
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-attribute-create">
    <AtriumPageHeader :title="t('showroom.structure.attributes.new')" />

    <AtriumIntegrationScreen screen-key="showroom.attribute-create">
      <UCard>
        <form class="flex max-w-xl flex-col gap-4" data-testid="showroom-attribute-create-form" @submit.prevent="create">
          <CatalogActionError :action="action" />

          <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.structure.code_hint')" :error="action.error('code')" required>
            <UInput v-model="form.code" required class="w-64" data-testid="showroom-attribute-create-code" />
          </UFormField>

          <UFormField :label="t('showroom.catalog.fields.type')" :hint="t('showroom.structure.attributes.type_hint')" :error="action.error('type')" required>
            <AtriumSelect v-model="form.type" :items="typeItems" :blank="t('showroom.structure.attributes.choose_type')" :blank-value="null" required class="w-64" data-testid="showroom-attribute-create-type" />
          </UFormField>

          <UFormField :label="t('showroom.catalog.fields.group')" :error="action.error('group')">
            <AtriumSelect v-model="form.group" :items="groupItems" :blank="t('showroom.structure.attributes.no_group')" :blank-value="null" class="w-64" data-testid="showroom-attribute-create-group" />
          </UFormField>

          <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
            <UInput v-model="form.label" class="w-64" data-testid="showroom-attribute-create-label" />
          </UFormField>

          <AttributeFlags v-model="flags" testid="showroom-attribute-create-flag" />

          <UFormField :label="t('showroom.structure.attributes.settings')" :hint="t('showroom.structure.attributes.settings_hint')" :error="settingsError ?? action.error('settings')">
            <UTextarea v-model="form.settings" :rows="3" class="w-full font-mono" data-testid="showroom-attribute-create-settings" />
          </UFormField>

          <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
            <UInput v-model="form.sort_order" type="number" min="0" class="w-32" data-testid="showroom-attribute-create-sort" />
          </UFormField>

          <div>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-attribute-create-submit">{{ t('showroom.structure.attributes.create') }}</UButton>
          </div>
        </form>
      </UCard>
    </AtriumIntegrationScreen>
  </div>
</template>
