<script setup lang="ts">
import { integrationData, useAtriumIntegration } from '@refactor-circus/atrium'
import type { operations } from '../../../../client/src/schema'

/**
 * The `showroom` settings panel (AC 61): how the catalog is exposed over
 * HTTP and MCP, read from `showroom.settings-summary.show`, in the rows the
 * v1 panel showed. The PHP side sends `data: {}`. A 403 shows the no-access
 * state, any other failure the error card.
 */
defineProps<{ data?: Record<string, unknown> }>()

type Summary = operations['showroom.settings-summary.show']['responses'][200]['content']['application/json']['data']

const { t } = useI18n()
const { client } = useAtriumIntegration('showroom')

const summary = shallowRef<Summary | null>(null)
const error = shallowRef<unknown>(null)

onMounted(async () => {
  if (!client) {
    return
  }

  try {
    summary.value = integrationData(await client.GET('/settings-summary')).data
  } catch (failure) {
    error.value = failure
  }
})

const forbidden = computed(() => atriumStatus(error.value) === 403)
const off = computed(() => t('showroom.settings.off'))

const sections = computed(() => {
  const value = summary.value

  if (!value) {
    return []
  }

  return [
    {
      key: 'api',
      title: t('showroom.settings.api'),
      rows: [
        { key: 'authorization', label: t('showroom.settings.authorization'), value: value.authorization ? t('showroom.settings.on') : off.value },
        { key: 'routes_prefix', label: t('showroom.settings.routes_prefix'), value: value.routes.enabled ? `/${value.routes.prefix}` : off.value },
        { key: 'per_page', label: t('showroom.settings.per_page'), value: String(value.pagination.per_page) },
        { key: 'max_per_page', label: t('showroom.settings.max_per_page'), value: String(value.pagination.max_per_page) }
      ]
    },
    {
      key: 'mcp',
      title: t('showroom.settings.mcp'),
      rows: [
        { key: 'web_transport', label: t('showroom.settings.web_transport'), value: value.mcp.web.enabled ? `/${value.mcp.web.route}` : off.value },
        { key: 'local_transport', label: t('showroom.settings.local_transport'), value: value.mcp.local.enabled ? value.mcp.local.handle : off.value }
      ]
    }
  ]
})
</script>

<template>
  <div data-testid="showroom-settings">
    <AtriumNoAccess v-if="forbidden" />
    <AtriumErrorCard v-else-if="error" :message="t('atrium.settings.failed')" />
    <USkeleton v-else-if="!summary" class="h-24" />
    <div v-else class="flex flex-col gap-4">
      <UCard v-for="section in sections" :key="section.key" :data-testid="`showroom-settings-${section.key}`">
        <template #header>
          <h3 class="font-medium">{{ section.title }}</h3>
        </template>
        <dl class="space-y-2 text-sm">
          <div v-for="row in section.rows" :key="row.key" class="flex justify-between gap-3" :data-row="row.key">
            <dt class="text-muted">{{ row.label }}</dt>
            <dd class="font-mono tabular-nums">{{ row.value }}</dd>
          </div>
        </dl>
      </UCard>
    </div>
  </div>
</template>
