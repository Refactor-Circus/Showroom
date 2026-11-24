<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  labelOf,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogOne,
  type ShowroomFlag
} from '../../components/catalog/catalog'
import FlagFields from '../../components/structure/FlagFields.vue'
import { editedLabels, sortOrder, type FlagForm } from '../../components/structure/structure'

/**
 * `showroom.flag`: one flag (`flags.show`), ported from v1's `flags/show`.
 * Its code is shown but never edited: assignments, search filters and API
 * clients name a flag by it. A save (`flags.update`) sends the labels (the
 * current locale's edited, the others kept), what it applies to and its sort
 * order; delete is confirmed (`flags.destroy`), and Showroom refuses it with
 * a message while the flag is still assigned.
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const code = computed(() => String(route.params.flag))

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogOne<ShowroomFlag>>(await showroom.GET('/flags/{flag}', { params: { path: { flag: code.value } } })).data, code)

const flag = computed(() => data.value)
const label = ref('')
const fields = ref<FlagForm>({ applies_to: [], sort_order: '0' })

watch(data, () => {
  if (flag.value) {
    label.value = flag.value.labels[locale.value] ?? ''
    fields.value = { applies_to: [...flag.value.applies_to], sort_order: String(flag.value.sort_order) }
  }
}, { immediate: true })

async function save(): Promise<void> {
  const current = flag.value

  if (!client || !current) {
    return
  }

  const body = {
    labels: editedLabels(current.labels, locale.value, label.value),
    applies_to: [...fields.value.applies_to],
    sort_order: sortOrder(fields.value.sort_order) ?? 0
  }

  if (await action.run(async () => showroomData(await client.PATCH('/flags/{flag}', { params: { path: { flag: code.value } }, body: untyped(body) })))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.structure.flags.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/flags/{flag}', { params: { path: { flag: code.value } } })))) {
    await navigateTo('/showroom/flags')
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-flag">
    <AtriumIntegrationScreen screen-key="showroom.flag" :record="code">
      <CatalogState :ready="!pending && flag !== null" :failed="failed" :status="status">
        <div v-if="flag" class="space-y-5">
          <AtriumPageHeader :title="labelOf(flag.labels, locale, flag.code)" data-testid="showroom-flag-title">
            <template #meta>
              <p>
                {{ t('showroom.catalog.fields.code') }}:
                <span class="font-mono" data-testid="showroom-flag-code">{{ flag.code }}</span>
              </p>
            </template>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-flag-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </AtriumPageHeader>

          <CatalogActionError :action="action" />

          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.structure.details') }}</h2>
            </template>
            <form class="flex flex-col gap-4" data-testid="showroom-flag-form" @submit.prevent="save">
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="label" class="w-64" data-testid="showroom-flag-label" />
              </UFormField>
              <FlagFields v-model="fields" :action="action" testid="showroom-flag" />
              <div>
                <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-flag-save">{{ t('showroom.catalog.save') }}</UButton>
              </div>
            </form>
          </UCard>

          <p class="text-sm text-muted">{{ t('showroom.structure.flags.delete_note') }}</p>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
