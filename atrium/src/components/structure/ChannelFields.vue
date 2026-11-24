<script setup lang="ts">
import type { CatalogAction, ShowroomLocale } from '../catalog/catalog'
import type { ChannelForm, ShowroomCategory } from './structure'

/**
 * The fields of a channel, shared by the create and edit forms (v1's
 * `channels/fields`): the locales it publishes in, its currencies (typed as a
 * comma-separated list) and its category tree.
 */
const form = defineModel<ChannelForm>({ required: true })

defineProps<{
  locales: readonly ShowroomLocale[]
  trees: readonly ShowroomCategory[]
  action: CatalogAction
  testid: string
}>()

const { t } = useI18n()

function toggle(code: string, event: Event): void {
  const on = (event.target as HTMLInputElement).checked

  form.value.locales = on ? [...form.value.locales.filter(entry => entry !== code), code] : form.value.locales.filter(entry => entry !== code)
}
</script>

<template>
  <div class="flex flex-col gap-3">
    <div>
      <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.structure.channels.locales') }}</p>
      <div class="flex flex-wrap gap-3">
        <label v-for="locale in locales" :key="locale.code" class="flex items-center gap-1.5 text-sm">
          <input
            type="checkbox"
            :checked="form.locales.includes(locale.code)"
            :data-testid="`${testid}-locale-${locale.code}`"
            @change="toggle(locale.code, $event)"
          >
          <span class="font-mono text-xs">{{ locale.code }}</span>
        </label>
        <span v-if="locales.length === 0" class="text-sm text-muted">{{ t('showroom.structure.channels.no_locales') }}</span>
      </div>
      <small v-if="action.error('locales')" class="text-xs text-error">{{ action.error('locales') }}</small>
    </div>

    <div class="flex flex-wrap items-end gap-3">
      <UFormField :label="t('showroom.structure.channels.currencies')" :hint="t('showroom.structure.channels.currencies_hint')" :error="action.error('currencies')">
        <UInput v-model="form.currencies" class="w-56" :data-testid="`${testid}-currencies`" />
      </UFormField>
      <UFormField :label="t('showroom.structure.channels.category_tree')" :error="action.error('category_tree')">
        <select v-model="form.category_tree" class="w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" :data-testid="`${testid}-tree`">
          <option value="">{{ t('showroom.catalog.none') }}</option>
          <option v-for="tree in trees" :key="tree.code" :value="tree.code">{{ tree.code }}</option>
        </select>
      </UFormField>
    </div>
  </div>
</template>
