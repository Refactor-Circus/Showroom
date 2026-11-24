<script setup lang="ts">
import { useShowroomStats } from './stats'

/**
 * `showroom.completeness`: the average completeness and the fully complete
 * products of each channel and locale, from `showroom.stats.show`
 * (`completeness`). Each row links to the product list filtered to the
 * products complete there, as in v1.
 */
defineProps<{ data?: Record<string, unknown>, settings?: Record<string, unknown> }>()

const { t } = useI18n()
const { data: stats, status, forbidden } = useShowroomStats()

const rows = computed(() => stats.value?.completeness ?? null)
</script>

<template>
  <div class="h-full" data-testid="showroom-completeness">
    <AtriumNoAccess v-if="forbidden" compact />
    <AtriumErrorCard v-else-if="status === 'error'" :message="t('atrium.widget.failed')" />
    <template v-else-if="rows">
      <p v-if="rows.length === 0" class="atrium-empty" data-testid="showroom-completeness-empty">
        {{ t('showroom.widgets.no_completeness_yet') }}
      </p>
      <ul v-else class="flex flex-col gap-3">
        <li
          v-for="row in rows"
          :key="`${row.channel}-${row.locale}`"
          data-testid="showroom-completeness-row"
          :data-slot="`${row.channel}-${row.locale}`"
        >
          <div class="flex items-center justify-between gap-3 text-sm">
            <RouterLink
              class="font-mono hover:underline"
              :to="{ path: '/showroom/products', query: { 'complete[scope]': row.channel, 'complete[locale]': row.locale, 'complete[min]': '100' } }"
            >
              {{ row.channel }} · {{ row.locale }}
            </RouterLink>
            <span class="tabular-nums">
              <span data-testid="showroom-completeness-average">{{ row.average }}%</span>
              <span class="text-muted"> — {{ t('showroom.widgets.fully_complete', { complete: row.complete, products: row.products }) }}</span>
            </span>
          </div>
          <UProgress class="mt-1" :model-value="row.average" :max="100" size="sm" />
        </li>
      </ul>
    </template>
    <div v-else class="space-y-2">
      <USkeleton class="h-4 w-2/3" />
      <USkeleton class="h-4 w-1/2" />
    </div>
  </div>
</template>
