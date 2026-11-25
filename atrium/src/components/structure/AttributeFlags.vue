<script setup lang="ts">
import { ATTRIBUTE_FLAGS, type AttributeFlags } from './structure'

/**
 * An attribute's three switches (unique, localizable, scopable), as v1's
 * flags partial offered them on the create and edit forms. Each is always
 * sent, so unticking one is saved rather than left out.
 */
const flags = defineModel<AttributeFlags>({ required: true })

defineProps<{ testid: string }>()

const { t } = useI18n()
</script>

<template>
  <div class="flex flex-col gap-2">
    <UCheckbox
      v-for="flag in ATTRIBUTE_FLAGS"
      :key="flag"
      v-model="flags[flag]"
      :label="t(`showroom.structure.attributes.${flag}`)"
      :data-testid="`${testid}-${flag}`"
    />
  </div>
</template>
