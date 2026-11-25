<script setup lang="ts">
import { PRODUCT_STATUSES, statusColor, useShowroomStats } from './stats'

/**
 * `showroom.product-status`: how many products sit in each review state, and
 * how many are live, from `showroom.stats.show` (`status`). Each count links
 * to the product list filtered to it, as in v1. The PHP side sends
 * `data: {}`; a 403 shows the no-access card, any other failure the error
 * card.
 */
defineProps<{ data?: Record<string, unknown>, settings?: Record<string, unknown> }>()

const { t } = useI18n()
const { data: stats, status, forbidden } = useShowroomStats()

const counts = computed(() => stats.value?.status ?? null)
</script>

<template>
  <div class="h-full" data-testid="showroom-product-status">
    <AtriumNoAccess v-if="forbidden" compact />
    <AtriumErrorCard v-else-if="status === 'error'" :message="t('atrium.widget.failed')" />
    <div v-else-if="counts" class="flex flex-wrap gap-2">
      <ULink
        v-for="key in PRODUCT_STATUSES"
        :key="key"
        :to="{ path: '/showroom/products', query: { status: key } }"
        class="flex items-center gap-2 rounded-md border border-default px-3 py-2 hover:bg-elevated"
        data-testid="showroom-product-status-count"
        :data-status="key"
      >
        <UBadge :color="statusColor(key)" variant="subtle" size="sm">{{ t(`showroom.statuses.${key}`) }}</UBadge>
        <span class="text-sm font-semibold tabular-nums">{{ counts.counts[key] ?? 0 }}</span>
      </ULink>
      <ULink
        :to="{ path: '/showroom/products', query: { published: '1' } }"
        class="flex items-center gap-2 rounded-md border border-default px-3 py-2 hover:bg-elevated"
        data-testid="showroom-product-status-count"
        data-status="published"
      >
        <UBadge color="success" variant="subtle" size="sm">{{ t('showroom.widgets.live') }}</UBadge>
        <span class="text-sm font-semibold tabular-nums">{{ counts.published }}</span>
      </ULink>
    </div>
    <div v-else class="flex gap-2">
      <USkeleton class="h-9 w-24" />
      <USkeleton class="h-9 w-24" />
    </div>
  </div>
</template>
