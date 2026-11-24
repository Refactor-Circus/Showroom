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
  type CatalogOne,
  type ShowroomLocale
} from '../../components/catalog/catalog'
import ChannelFields from '../../components/structure/ChannelFields.vue'
import {
  channelBody,
  channelForm,
  editedLabels,
  loadTrees,
  type ShowroomCategory,
  type StructureChannel
} from '../../components/structure/structure'

/**
 * `showroom.channel`: one channel (`channels.show`) with its locales, ported
 * from v1's `channels/show`. A save (`channels.update`) sends the labels (the
 * current locale's edited, the others kept), the locales ticked from
 * `locales.index`, the currencies and the category tree; delete is
 * confirmed (`channels.destroy`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const code = computed(() => String(route.params.channel))

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogOne<StructureChannel>>(await showroom.GET('/channels/{channel}', { params: { path: { channel: code.value } } })).data, code)

const locales = usePickList<ShowroomLocale>(async showroom =>
  showroomData<CatalogList<ShowroomLocale>>(await showroom.GET('/locales', { params: { query: { per_page: ALL } } })))

const trees = usePickList<ShowroomCategory>(loadTrees)

const channel = computed(() => data.value)
const label = ref('')
const fields = ref(channelForm())

watch(data, () => {
  if (channel.value) {
    label.value = channel.value.labels[locale.value] ?? ''
    fields.value = channelForm(channel.value)
  }
}, { immediate: true })

async function save(): Promise<void> {
  const current = channel.value

  if (!client || !current) {
    return
  }

  const body = { labels: editedLabels(current.labels, locale.value, label.value), ...channelBody(fields.value) }

  if (await action.run(async () => showroomData(await client.PATCH('/channels/{channel}', { params: { path: { channel: code.value } }, body: untyped(body) })))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.structure.channels.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/channels/{channel}', { params: { path: { channel: code.value } } })))) {
    await navigateTo('/showroom/channels')
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-channel">
    <AtriumIntegrationScreen screen-key="showroom.channel" :record="code">
      <CatalogState :ready="!pending && channel !== null" :failed="failed" :status="status">
        <div v-if="channel" class="space-y-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h1 class="text-xl font-semibold" data-testid="showroom-channel-title">{{ labelOf(channel.labels, locale, channel.code) }}</h1>
              <p class="font-mono text-sm text-muted">{{ channel.code }}</p>
            </div>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-channel-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </div>

          <CatalogActionError :action="action" />

          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('showroom.structure.details') }}</h2>
            </template>
            <form class="flex flex-col gap-4" data-testid="showroom-channel-form" @submit.prevent="save">
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="label" class="w-64" data-testid="showroom-channel-label" />
              </UFormField>
              <ChannelFields v-model="fields" :locales="locales" :trees="trees" :action="action" testid="showroom-channel" />
              <div>
                <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-channel-save">{{ t('showroom.catalog.save') }}</UButton>
              </div>
            </form>
          </UCard>

          <p class="text-sm text-muted">{{ t('showroom.structure.channels.delete_note') }}</p>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
