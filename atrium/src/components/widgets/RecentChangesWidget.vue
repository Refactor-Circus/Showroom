<script setup lang="ts">
import { formatDate, productModelPath, productPath, type ShowroomStats, useShowroomStats } from './stats'

/**
 * `showroom.recent-changes`: the newest product and product model versions,
 * newest first, from `showroom.stats.show` (`recent_versions`). Each links to
 * its product or product model while that still exists. Showroom's API names
 * the author by id only, as v1 did when it had no name.
 */
defineProps<{ data?: Record<string, unknown>, settings?: Record<string, unknown> }>()

type Version = ShowroomStats['recent_versions'][number]

const { t } = useI18n()
const { data: stats, status, forbidden } = useShowroomStats()

const versions = computed(() => stats.value?.recent_versions ?? null)

/** Where a version's subject lives, or null once it is gone. */
function subjectPath(version: Version): string | null {
  if (version.subject.key === null) {
    return null
  }

  return version.subject.type === 'product' ? productPath(version.subject.id) : productModelPath(version.subject.key)
}
</script>

<template>
  <div class="h-full" data-testid="showroom-recent-changes">
    <AtriumNoAccess v-if="forbidden" compact />
    <AtriumErrorCard v-else-if="status === 'error'" :message="t('atrium.widget.failed')" />
    <template v-else-if="versions">
      <p v-if="versions.length === 0" class="atrium-empty" data-testid="showroom-recent-changes-empty">
        {{ t('showroom.widgets.no_changes') }}
      </p>
      <ul v-else class="flex flex-col divide-y divide-default">
        <li
          v-for="version in versions"
          :key="`${version.subject.type}-${version.subject.id}-${version.version}`"
          class="flex items-center justify-between gap-3 py-2 text-sm"
          data-testid="showroom-recent-change"
          :data-version="version.version"
        >
          <span class="flex items-center gap-2">
            <RouterLink v-if="subjectPath(version)" class="font-mono hover:underline" :to="subjectPath(version)!">{{ version.subject.key }}</RouterLink>
            <span class="font-mono text-muted">v{{ version.version }}</span>
            <UBadge color="neutral" variant="subtle" size="sm">{{ version.action }}</UBadge>
          </span>
          <span class="text-right text-muted">
            <template v-if="version.author">{{ t('showroom.widgets.by', { author: String(version.author.id) }) }} · </template>
            {{ formatDate(version.created_at) }}
          </span>
        </li>
      </ul>
    </template>
    <div v-else class="space-y-2">
      <USkeleton class="h-4 w-2/3" />
      <USkeleton class="h-4 w-1/2" />
    </div>
  </div>
</template>
