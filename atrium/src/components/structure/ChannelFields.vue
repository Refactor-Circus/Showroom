<script setup lang="ts">
import type { CatalogAction, ShowroomLocale } from '../catalog/catalog'
import type { ChannelForm, ShowroomCategory } from './structure'

/**
 * The fields of a channel, shared by the create and edit forms (v1's
 * `channels/fields`): the locales it publishes in, its currencies (typed as a
 * comma-separated list) and its category tree.
 */
const form = defineModel<ChannelForm>({ required: true })

const props = defineProps<{
  locales: readonly ShowroomLocale[]
  trees: readonly ShowroomCategory[]
  action: CatalogAction
  testid: string
}>()

const { t } = useI18n()

const treeItems = computed(() => props.trees.map(tree => ({ label: tree.code, value: tree.code })))

function toggle(code: string, checked: boolean | 'indeterminate'): void {
  const on = checked === true

  form.value.locales = on ? [...form.value.locales.filter(entry => entry !== code), code] : form.value.locales.filter(entry => entry !== code)
}
</script>

<template>
  <div class="flex flex-col gap-3">
    <div>
      <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.structure.channels.locales') }}</p>
      <div class="flex flex-wrap gap-3">
        <UCheckbox
          v-for="locale in locales"
          :key="locale.code"
          :model-value="form.locales.includes(locale.code)"
          :label="locale.code"
          :ui="{ label: 'font-mono text-xs' }"
          :data-testid="`${testid}-locale-${locale.code}`"
          @update:model-value="toggle(locale.code, $event)"
        />
        <span v-if="locales.length === 0" class="text-sm text-muted">{{ t('showroom.structure.channels.no_locales') }}</span>
      </div>
      <small v-if="action.error('locales')" class="text-xs text-error">{{ action.error('locales') }}</small>
    </div>

    <div class="flex flex-wrap items-end gap-3">
      <UFormField :label="t('showroom.structure.channels.currencies')" :hint="t('showroom.structure.channels.currencies_hint')" :error="action.error('currencies')">
        <UInput v-model="form.currencies" class="w-56" :data-testid="`${testid}-currencies`" />
      </UFormField>
      <UFormField :label="t('showroom.structure.channels.category_tree')" :error="action.error('category_tree')">
        <AtriumSelect v-model="form.category_tree" :items="treeItems" :blank="t('showroom.catalog.none')" :blank-value="null" class="w-56" :data-testid="`${testid}-tree`" />
      </UFormField>
    </div>
  </div>
</template>
