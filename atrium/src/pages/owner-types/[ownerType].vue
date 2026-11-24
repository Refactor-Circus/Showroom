<script setup lang="ts">
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
  type CatalogOne
} from '../../components/catalog/catalog'
import { editedLabels } from '../../components/structure/structure'
import OwnerTypeRules from '../../components/owners/OwnerTypeRules.vue'
import { ownerTypeRules, ownerTypeRulesBody, type ShowroomOwnerType } from '../../components/owners/owners'

/**
 * `showroom.owner-type`: one owner type (`owner-types.show`), ported from
 * v1's `owner-types/show`. A save (`owner-types.update`) sends the labels
 * (the current locale's edited, the others kept) and the chain rules:
 * `parent_types` null for any parent, else the ticked codes. Delete is
 * confirmed (`owner-types.destroy`); Showroom refuses it with a message
 * while owners use the type.
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const code = computed(() => String(route.params.ownerType))

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogOne<ShowroomOwnerType>>(await showroom.GET('/owner-types/{ownerType}', { params: { path: { ownerType: code.value } } })).data, code)

const type = computed(() => data.value)

const types = usePickList(async showroom => showroomData<CatalogList<ShowroomOwnerType>>(await showroom.GET('/owner-types', { params: { query: { per_page: ALL } } })))

const label = ref('')
const rules = ref(ownerTypeRules())

watch(data, () => {
  label.value = type.value?.labels[locale.value] ?? ''
  rules.value = ownerTypeRules(type.value)
}, { immediate: true })

async function save(): Promise<void> {
  const current = type.value

  if (!client || !current) {
    return
  }

  const all = types.value.map(entry => entry.code)
  const body = {
    labels: editedLabels(current.labels, locale.value, label.value),
    // Keep a parent the pick list has not loaded (or no longer lists) in Showroom's order.
    ...ownerTypeRulesBody(rules.value, [...all, ...rules.value.parent_types.filter(entry => !all.includes(entry))])
  }

  if (await action.run(async () => showroomData(await client.PATCH('/owner-types/{ownerType}', { params: { path: { ownerType: code.value } }, body: untyped(body) })))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.owner_types.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/owner-types/{ownerType}', { params: { path: { ownerType: code.value } } })))) {
    await navigateTo('/showroom/owner-types')
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-owner-type">
    <AtriumIntegrationScreen screen-key="showroom.owner-type" :record="code">
      <CatalogState :ready="!pending && type !== null" :failed="failed" :status="status">
        <div v-if="type" class="space-y-5">
          <AtriumPageHeader :overline="t('showroom.owner_types.title')">
            <template #title>
              <span class="truncate" data-testid="showroom-owner-type-title">{{ labelOf(type.labels, locale, type.code) }}</span>
            </template>
            <template #description>
              <span class="font-mono text-sm">{{ type.code }}</span>
            </template>
            <UButton
              :to="`/showroom/owners?type=${encodeURIComponent(type.code)}`"
              variant="outline"
              icon="i-lucide-building-2"
              data-testid="showroom-owner-type-owners"
            >
              {{ t('showroom.owner_types.owners_of_type', { count: type.owners_count ?? 0 }) }}
            </UButton>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-owner-type-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </AtriumPageHeader>

          <CatalogActionError :action="action" />

          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.owner_types.rules') }}</h2>
            </template>
            <form class="flex flex-col gap-4" data-testid="showroom-owner-type-form" @submit.prevent="save">
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="label" class="w-64" data-testid="showroom-owner-type-label" />
              </UFormField>
              <OwnerTypeRules v-model="rules" :types="types" :action="action" testid="showroom-owner-type" />
              <div>
                <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-owner-type-save">{{ t('showroom.catalog.save') }}</UButton>
              </div>
            </form>
          </UCard>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
