<script setup lang="ts">
import { formatDate, productPath, useShowroomStats } from './stats'

/**
 * `showroom.review-queue`: the products in review the longest, oldest first,
 * and how many wait in all, from `showroom.stats.show` (`review_queue`).
 * Each product links to its page, and the count to the list of products in
 * review, as in v1.
 */
defineProps<{ data?: Record<string, unknown>, settings?: Record<string, unknown> }>()

const { t } = useI18n()
const { data: stats, status, forbidden } = useShowroomStats()

const queue = computed(() => stats.value?.review_queue ?? null)
</script>

<template>
  <div class="h-full" data-testid="showroom-review-queue">
    <AtriumNoAccess v-if="forbidden" compact />
    <AtriumErrorCard v-else-if="status === 'error'" :message="t('atrium.widget.failed')" />
    <template v-else-if="queue">
      <AtriumState v-if="queue.products.length === 0" compact :title="t('showroom.widgets.nothing_in_review')" data-testid="showroom-review-queue-empty" />
      <template v-else>
        <p class="mb-2 text-sm text-muted">
          <ULink
            class="hover:underline"
            :to="{ path: '/showroom/products', query: { status: 'in_review' } }"
            data-testid="showroom-review-queue-total"
          >
            {{ t('showroom.widgets.waiting_review', { count: queue.total }) }}
          </ULink>
        </p>
        <ul class="flex flex-col divide-y divide-default">
          <li
            v-for="product in queue.products"
            :key="product.id"
            class="flex items-center justify-between gap-3 py-2 text-sm"
            data-testid="showroom-review-queue-product"
            :data-product="product.identifier"
          >
            <ULink class="font-mono hover:underline" :to="productPath(product.id)">{{ product.identifier }}</ULink>
            <span class="text-muted">{{ formatDate(product.updated_at) }}</span>
          </li>
        </ul>
      </template>
    </template>
    <div v-else class="space-y-2">
      <USkeleton class="h-4 w-1/3" />
      <USkeleton class="h-4 w-2/3" />
    </div>
  </div>
</template>
