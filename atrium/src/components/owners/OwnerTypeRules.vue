<script setup lang="ts">
import { labelOf, type CatalogAction } from '../catalog/catalog'
import type { OwnerTypeRules, ShowroomOwnerType } from './owners'

/**
 * An owner type's chain rules (v1's `owner-types/rules`): any parent, or only
 * the ticked parent types; whether an owner of it may be a root; and whether
 * it owns products. `testid` prefixes each input's test id.
 */
const props = defineProps<{ types: ShowroomOwnerType[], action: CatalogAction, testid: string }>()

const rules = defineModel<OwnerTypeRules>({ required: true })

const { t, locale } = useI18n()

function toggle(code: string, on: boolean): void {
  rules.value.parent_types = on
    ? [...rules.value.parent_types.filter(entry => entry !== code), code]
    : rules.value.parent_types.filter(entry => entry !== code)
}
</script>

<template>
  <div class="flex flex-col gap-3" :data-testid="`${props.testid}-rules`">
    <div>
      <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.owner_types.parent_types') }}</p>
      <UCheckbox v-model="rules.any_parent" :label="t('showroom.owner_types.any_parent')" :data-testid="`${props.testid}-any-parent`" />
      <div v-if="!rules.any_parent" class="mt-2 flex flex-wrap gap-3">
        <UCheckbox
          v-for="type in props.types"
          :key="type.code"
          :model-value="rules.parent_types.includes(type.code)"
          :data-testid="`${props.testid}-parent-${type.code}`"
          @update:model-value="toggle(type.code, $event === true)"
        >
          <template #label>
            <span class="font-mono text-xs">{{ type.code }}</span>
            <span v-if="labelOf(type.labels, locale, type.code) !== type.code" class="ms-1.5 text-muted">{{ labelOf(type.labels, locale, type.code) }}</span>
          </template>
        </UCheckbox>
        <span v-if="props.types.length === 0" class="text-sm text-muted">{{ t('showroom.owner_types.no_types') }}</span>
      </div>
      <small v-if="props.action.error('parent_types')" class="text-xs text-error">{{ props.action.error('parent_types') }}</small>
    </div>
    <UCheckbox v-model="rules.can_be_root" :label="t('showroom.owner_types.can_be_root')" :data-testid="`${props.testid}-root`" />
    <UCheckbox v-model="rules.owns_products" :label="t('showroom.owner_types.owns_products')" :data-testid="`${props.testid}-owns`" />
  </div>
</template>
