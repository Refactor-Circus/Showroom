<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  attributePath,
  labelOf,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogOne,
  type ShowroomAttribute
} from '../../components/catalog/catalog'
import { editedLabels, sortOrder, type ShowroomAttributeGroup } from '../../components/structure/structure'

/**
 * `showroom.attribute-group`: one attribute group (`attribute-groups.show`)
 * and the attributes in it, ported from v1's `attribute-groups/show`. A save
 * (`attribute-groups.update`) sends the labels (the current locale's edited,
 * the others kept) and the sort order; delete is confirmed. An attribute
 * joins a group from its own page.
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const code = computed(() => String(route.params.group))

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogOne<ShowroomAttributeGroup>>(await showroom.GET('/attribute-groups/{group}', { params: { path: { group: code.value } } })).data, code)

const group = computed(() => data.value)
const members = computed(() => group.value?.attributes ?? [])

const memberColumns = computed<TableColumn<ShowroomAttribute>[]>(() => [
  { accessorKey: 'code', header: t('showroom.catalog.fields.code') },
  { id: 'label', header: t('showroom.catalog.fields.label') },
  { accessorKey: 'type', header: t('showroom.catalog.fields.type') }
])
const form = reactive({ label: '', sort_order: '0' })

watch(data, () => {
  if (group.value) {
    form.label = group.value.labels[locale.value] ?? ''
    form.sort_order = String(group.value.sort_order)
  }
}, { immediate: true })

async function save(): Promise<void> {
  const current = group.value

  if (!client || !current) {
    return
  }

  const body = { labels: editedLabels(current.labels, locale.value, form.label), sort_order: sortOrder(form.sort_order) ?? 0 }

  if (await action.run(async () => showroomData(await client.PATCH('/attribute-groups/{group}', { params: { path: { group: code.value } }, body: untyped(body) })))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.structure.groups.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/attribute-groups/{group}', { params: { path: { group: code.value } } })))) {
    await navigateTo('/showroom/attribute-groups')
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-attribute-group">
    <AtriumIntegrationScreen screen-key="showroom.attribute-group" :record="code">
      <CatalogState :ready="!pending && group !== null" :failed="failed" :status="status">
        <div v-if="group" class="space-y-5">
          <AtriumPageHeader :title="labelOf(group.labels, locale, group.code)" :description="group.code" data-testid="showroom-group-title">
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-group-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </AtriumPageHeader>

          <CatalogActionError :action="action" />

          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.structure.details') }}</h2>
            </template>
            <form class="flex flex-wrap items-end gap-3" data-testid="showroom-group-form" @submit.prevent="save">
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="form.label" class="w-64" data-testid="showroom-group-label" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
                <UInput v-model="form.sort_order" type="number" min="0" class="w-28" data-testid="showroom-group-sort" />
              </UFormField>
              <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-group-save">{{ t('showroom.catalog.save') }}</UButton>
            </form>
          </UCard>

          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.structure.groups.members') }}</h2>
            </template>
            <UTable :data="members" :columns="memberColumns">
              <template #empty>
                <AtriumState compact :title="t('showroom.structure.groups.no_members')" data-testid="showroom-group-attributes-empty" />
              </template>
              <template #code-cell="{ row: { original: attribute } }">
                <span :data-testid="`showroom-group-attribute-${attribute.code}`">
                  <ULink :to="attributePath(attribute.code)" class="font-mono hover:underline">{{ attribute.code }}</ULink>
                </span>
              </template>
              <template #label-cell="{ row: { original: attribute } }">{{ labelOf(attribute.labels, locale, attribute.code) }}</template>
              <template #type-cell="{ row: { original: attribute } }"><UBadge color="neutral" variant="subtle">{{ attribute.type }}</UBadge></template>
            </UTable>
          </UCard>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
