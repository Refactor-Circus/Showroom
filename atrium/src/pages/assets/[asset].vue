<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  blankToNull,
  labelOf,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogOne
} from '../../components/catalog/catalog'
import { editedLabels } from '../../components/structure/structure'
import {
  ASSET_LINK_TYPES,
  appendLabels,
  formatSize,
  isImage,
  linkTargetPath,
  type AssetLink,
  type AssetLinkType,
  type ShowroomAsset
} from '../../components/assets/assets'

/**
 * `showroom.asset`: one asset (`assets.show`) with its file and the records
 * it is linked to, ported from v1's `assets/show`. A save
 * (`assets.update`) sends the labels (the current locale's edited, the
 * others kept) as JSON, or as multipart with the file when one is picked to
 * replace it. Links are added with their type, target and role
 * (`assets.links.store`) and removed by the same three
 * (`assets.links.destroy`). Delete is confirmed (`assets.destroy`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const code = computed(() => String(route.params.asset))

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogOne<ShowroomAsset>>(await showroom.GET('/assets/{asset}', { params: { path: { asset: code.value } } })).data, code)

const asset = computed(() => data.value)
const links = computed(() => asset.value?.links ?? [])

const label = ref('')
const file = shallowRef<File | null>(null)
const fileInput = useTemplateRef<{ inputRef: HTMLInputElement | null }>('fileInput')

const linkTypeItems = computed(() => ASSET_LINK_TYPES.map(type => ({ label: t(`showroom.assets.link_types.${type}`), value: type })))

const linkColumns = computed<TableColumn<AssetLink>[]>(() => [
  { accessorKey: 'type', header: t('showroom.catalog.fields.type') },
  { accessorKey: 'target', header: t('showroom.assets.target') },
  { accessorKey: 'role', header: t('showroom.assets.role') },
  { id: 'actions', header: '', meta: { class: { td: 'text-right' } } }
])
const link = reactive<{ type: AssetLinkType, target: string, role: string }>({ type: 'product', target: '', role: 'media' })

watch(data, () => {
  label.value = asset.value?.labels[locale.value] ?? ''
}, { immediate: true })

function choose(event: Event): void {
  file.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function save(): Promise<void> {
  const current = asset.value

  if (!client || !current) {
    return
  }

  const labels = editedLabels(current.labels, locale.value, label.value)
  const chosen = file.value

  const saved = await action.run(async () => {
    if (!chosen) {
      return showroomData(await client.PATCH('/assets/{asset}', { params: { path: { asset: code.value } }, body: untyped({ labels }) }))
    }

    const form = new FormData()
    form.append('file', chosen)
    appendLabels(form, labels)

    return showroomData(await client.PATCH('/assets/{asset}', {
      params: { path: { asset: code.value } },
      body: form as never,
      bodySerializer: body => body as FormData
    }))
  })

  if (saved) {
    file.value = null

    if (fileInput.value?.inputRef) {
      fileInput.value.inputRef.value = ''
    }

    await reload()
  }
}

async function attach(): Promise<void> {
  if (!client) {
    return
  }

  const role = blankToNull(link.role)
  const body = { type: link.type, target: link.target.trim(), ...(role ? { role } : {}) }

  if (await action.run(async () => showroomData(await client.POST('/assets/{asset}/links', { params: { path: { asset: code.value } }, body })))) {
    link.target = ''
    await reload()
  }
}

async function detach(entry: AssetLink): Promise<void> {
  if (!client) {
    return
  }

  const query = { type: entry.type, target: entry.target, ...(entry.role ? { role: entry.role } : {}) }

  if (await action.run(async () => showroomData(await client.DELETE('/assets/{asset}/links', { params: { path: { asset: code.value }, query } })))) {
    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('showroom.assets.confirm_delete'))) {
    return
  }

  if (await action.run(async () => showroomData(await client.DELETE('/assets/{asset}', { params: { path: { asset: code.value } } })))) {
    await navigateTo('/showroom/assets')
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-asset">
    <AtriumIntegrationScreen screen-key="showroom.asset" :record="code">
      <CatalogState :ready="!pending && asset !== null" :failed="failed" :status="status">
        <div v-if="asset" class="space-y-5">
          <AtriumPageHeader :overline="t('showroom.assets.title')" :description="asset.filename">
            <template #title>
              <span class="truncate" data-testid="showroom-asset-title">{{ labelOf(asset.labels, locale, asset.code) }}</span>
            </template>
            <UButton :to="asset.url" target="_blank" external variant="outline" icon="i-lucide-external-link" data-testid="showroom-asset-open">{{ t('showroom.assets.open_file') }}</UButton>
            <UButton color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" data-testid="showroom-asset-delete" @click="destroy">{{ t('showroom.catalog.delete') }}</UButton>
          </AtriumPageHeader>

          <CatalogActionError :action="action" />

          <div class="grid gap-5 lg:grid-cols-3">
            <UCard data-testid="showroom-asset-file-card">
              <img v-if="isImage(asset)" :src="asset.url" :alt="labelOf(asset.labels, locale, asset.code)" class="mb-3 w-full rounded-md object-contain" data-testid="showroom-asset-image">
              <AtriumDetails
                :items="[
                  { key: 'code', label: t('showroom.catalog.fields.code'), value: asset.code, mono: true },
                  { key: 'type', label: t('showroom.catalog.fields.type'), value: asset.mime_type },
                  { key: 'size', label: t('showroom.assets.size'), value: formatSize(asset.size), testid: 'showroom-asset-size' },
                  { key: 'checksum', label: t('showroom.assets.checksum'), value: asset.checksum, mono: true, testid: 'showroom-asset-checksum' }
                ]"
                :empty="t('showroom.catalog.none')"
              />
            </UCard>

            <div class="space-y-5 lg:col-span-2">
              <UCard>
                <template #header>
                  <h2 class="font-semibold">{{ t('showroom.assets.details') }}</h2>
                </template>
                <form class="flex flex-wrap items-end gap-3" data-testid="showroom-asset-form" @submit.prevent="save">
                  <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                    <UInput v-model="label" class="w-64" data-testid="showroom-asset-label" />
                  </UFormField>
                  <UFormField :label="t('showroom.assets.replace_file')" :error="action.error('file')">
                    <UInput ref="fileInput" type="file" data-testid="showroom-asset-file" @change="choose" />
                  </UFormField>
                  <UButton type="submit" icon="i-lucide-check" :loading="action.busy.value" data-testid="showroom-asset-save">{{ t('showroom.catalog.save') }}</UButton>
                </form>
              </UCard>

              <UCard data-testid="showroom-asset-links">
                <template #header>
                  <h2 class="font-semibold">{{ t('showroom.assets.links') }}</h2>
                </template>
                <div class="space-y-4">
                  <UTable :data="links" :columns="linkColumns">
                    <template #empty>
                      <AtriumState compact :title="t('showroom.assets.no_links')" data-testid="showroom-asset-links-empty" />
                    </template>
                    <template #type-cell="{ row: { original: entry } }">
                      <span :data-testid="`showroom-asset-link-${entry.type}-${entry.target}`">{{ t(`showroom.assets.link_types.${entry.type}`) }}</span>
                    </template>
                    <template #target-cell="{ row: { original: entry } }">
                      <ULink :to="linkTargetPath(entry)" class="font-mono text-xs hover:underline">{{ entry.target }}</ULink>
                    </template>
                    <template #role-cell="{ row: { original: entry } }">{{ entry.role ?? t('showroom.catalog.none') }}</template>
                    <template #actions-cell="{ row: { original: entry } }">
                      <UButton
                        variant="ghost"
                        color="neutral"
                        size="sm"
                        icon="i-lucide-unlink"
                        :loading="action.busy.value"
                        :data-testid="`showroom-asset-unlink-${entry.type}-${entry.target}`"
                        @click="detach(entry)"
                      >
                        {{ t('showroom.assets.unlink') }}
                      </UButton>
                    </template>
                  </UTable>

                  <form class="flex flex-wrap items-end gap-3" data-testid="showroom-asset-link-form" @submit.prevent="attach">
                    <UFormField :label="t('showroom.catalog.fields.type')" :error="action.error('type')" required>
                      <USelect v-model="link.type" :items="linkTypeItems" class="w-40" data-testid="showroom-asset-link-type" />
                    </UFormField>
                    <UFormField :label="t('showroom.assets.target')" :hint="t('showroom.assets.target_hint')" :error="action.error('target')" required>
                      <UInput v-model="link.target" required class="w-48" data-testid="showroom-asset-link-target" />
                    </UFormField>
                    <UFormField :label="t('showroom.assets.role')" :error="action.error('role')">
                      <UInput v-model="link.role" class="w-36" data-testid="showroom-asset-link-role" />
                    </UFormField>
                    <UButton type="submit" icon="i-lucide-link" :loading="action.busy.value" data-testid="showroom-asset-link-submit">{{ t('showroom.assets.link') }}</UButton>
                  </form>
                </div>
              </UCard>
            </div>
          </div>
        </div>
      </CatalogState>
    </AtriumIntegrationScreen>
  </div>
</template>
