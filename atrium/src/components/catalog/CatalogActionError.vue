<script setup lang="ts">
import type { CatalogAction } from './catalog'

/**
 * A failed mutation that is not a validation error: the no-access notice on
 * a 403, else Showroom's message (a workflow refusal answers 409 with one).
 */
defineProps<{ action: CatalogAction }>()

const { t } = useI18n()
</script>

<template>
  <AtriumNoAccess v-if="action.forbidden.value" compact :title="t('showroom.catalog.state.action_forbidden')" />
  <UAlert v-else-if="action.message.value" color="error" variant="subtle" :title="action.message.value" data-testid="showroom-action-error" />
</template>
