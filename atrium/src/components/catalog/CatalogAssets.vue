<script setup lang="ts">
import { showroomData, useCatalogAction, useShowroom, type CatalogAsset, type CatalogOne } from './catalog'
import CatalogActionError from './CatalogActionError.vue'

/**
 * The assets of a product or product model, own then inherited (v1's
 * `partials/assets`), each linking to the asset's page, with a form that
 * uploads a file and links it: `assets.store`, then `assets.links.store`
 * with the record as target.
 */
const props = defineProps<{ assets: CatalogAsset[], linkType: 'product' | 'product_model', linkTarget: string }>()

const emit = defineEmits<{ changed: [] }>()

const { t } = useI18n()
const client = useShowroom()
const action = useCatalogAction()

const file = shallowRef<File | null>(null)
const role = ref('image')
const input = useTemplateRef<{ inputRef: HTMLInputElement | null }>('fileInput')

function choose(event: Event): void {
  file.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

function isImage(asset: CatalogAsset): boolean {
  return Boolean(asset.url) && (asset.mime_type ?? '').startsWith('image/')
}

function caption(asset: CatalogAsset): string {
  return [asset.role, asset.inherited ? t('showroom.catalog.inherited') : asset.code].filter(part => part).join(' · ')
}

async function upload(): Promise<void> {
  const chosen = file.value

  if (!client || !chosen) {
    return
  }

  const succeeded = await action.run(async () => {
    const form = new FormData()
    form.append('file', chosen)

    const asset = showroomData<CatalogOne<{ code: string }>>(await client.POST('/assets', {
      body: form as never,
      bodySerializer: body => body as FormData
    })).data

    showroomData(await client.POST('/assets/{asset}/links', {
      params: { path: { asset: asset.code } },
      body: { type: props.linkType, target: props.linkTarget, ...(role.value.trim() ? { role: role.value.trim() } : {}) }
    }))
  })

  if (succeeded) {
    file.value = null

    if (input.value?.inputRef) {
      input.value.inputRef.value = ''
    }

    emit('changed')
  }
}
</script>

<template>
  <UCard data-testid="showroom-assets">
    <template #header>
      <h2 class="font-semibold">{{ t('showroom.catalog.assets.title') }}</h2>
    </template>

    <div class="space-y-4">
      <AtriumState v-if="props.assets.length === 0" compact :title="t('showroom.catalog.assets.none')" data-testid="showroom-assets-empty" />
      <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
        <ULink
          v-for="asset in props.assets"
          :key="`${asset.inherited ? 'inherited' : 'own'}-${asset.code}`"
          :to="`/showroom/assets/${encodeURIComponent(asset.code)}`"
          class="flex flex-col gap-2 rounded-md border border-default p-2 hover:border-primary"
          :data-testid="`showroom-asset-${asset.code}`"
        >
          <img v-if="isImage(asset)" :src="asset.url ?? undefined" :alt="asset.code" class="aspect-square w-full rounded object-cover">
          <span v-else class="flex aspect-square items-center justify-center rounded bg-elevated font-mono text-xs text-muted">{{ asset.mime_type ?? asset.code }}</span>
          <span class="truncate text-xs">{{ caption(asset) }}</span>
        </ULink>
      </div>

      <CatalogActionError :action="action" />

      <form class="flex flex-wrap items-start gap-3" data-testid="showroom-asset-upload" @submit.prevent="upload">
        <UFormField :label="t('showroom.catalog.assets.upload_and_link')" :error="action.error('file')">
          <UInput ref="fileInput" type="file" required data-testid="showroom-asset-file" @change="choose" />
        </UFormField>
        <UFormField :label="t('showroom.catalog.assets.role')" :error="action.error('role')">
          <UInput v-model="role" class="w-32" data-testid="showroom-asset-role" />
        </UFormField>
        <UButton type="submit" icon="i-lucide-upload" :loading="action.busy.value" class="mt-field-label" data-testid="showroom-asset-upload-submit">{{ t('showroom.catalog.assets.upload') }}</UButton>
      </form>
    </div>
  </UCard>
</template>
