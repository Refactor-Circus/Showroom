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

function checkedOf(event: Event): boolean {
  return (event.target as HTMLInputElement).checked
}
</script>

<template>
  <div class="flex flex-col gap-3" :data-testid="`${props.testid}-rules`">
    <div>
      <p class="mb-1 text-xs font-medium uppercase text-muted">{{ t('showroom.owner_types.parent_types') }}</p>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="rules.any_parent" type="checkbox" :data-testid="`${props.testid}-any-parent`">
        {{ t('showroom.owner_types.any_parent') }}
      </label>
      <div v-if="!rules.any_parent" class="mt-2 flex flex-wrap gap-3">
        <label v-for="type in props.types" :key="type.code" class="flex items-center gap-1.5 text-sm">
          <input
            type="checkbox"
            :value="type.code"
            :checked="rules.parent_types.includes(type.code)"
            :data-testid="`${props.testid}-parent-${type.code}`"
            @change="toggle(type.code, checkedOf($event))"
          >
          <span class="font-mono text-xs">{{ type.code }}</span>
          <span v-if="labelOf(type.labels, locale, type.code) !== type.code" class="text-muted">{{ labelOf(type.labels, locale, type.code) }}</span>
        </label>
        <span v-if="props.types.length === 0" class="text-sm text-muted">{{ t('showroom.owner_types.no_types') }}</span>
      </div>
      <small v-if="props.action.error('parent_types')" class="text-xs text-error">{{ props.action.error('parent_types') }}</small>
    </div>
    <label class="flex items-center gap-2 text-sm">
      <input v-model="rules.can_be_root" type="checkbox" :data-testid="`${props.testid}-root`">
      {{ t('showroom.owner_types.can_be_root') }}
    </label>
    <label class="flex items-center gap-2 text-sm">
      <input v-model="rules.owns_products" type="checkbox" :data-testid="`${props.testid}-owns`">
      {{ t('showroom.owner_types.owns_products') }}
    </label>
  </div>
</template>
