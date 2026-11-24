<script setup lang="ts">
import {
  availableTransitions,
  formatDate,
  showroomData,
  statusColor,
  TRANSITIONS,
  useCatalogAction,
  useShowroom,
  type ProductTransition,
  type ShowroomProduct,
  type ShowroomVersion
} from './catalog'
import CatalogActionError from './CatalogActionError.vue'

/**
 * A product's workflow (v1's `products/workflow`): its status and live
 * version, the transitions open from here (`products.transitions.store`,
 * with an optional comment), completeness per channel and locale, and the
 * latest history (`products.versions.index`) with revert
 * (`products.revert`, confirmed) on every entry but the newest.
 */
const props = defineProps<{ product: ShowroomProduct, versions: ShowroomVersion[] }>()

const emit = defineEmits<{ changed: [] }>()

const { t } = useI18n()
const client = useShowroom()
const action = useCatalogAction()

const comment = ref('')

const transitions = computed(() => availableTransitions(props.product.status, props.product.published_version))
const completeness = computed(() => props.product.completeness ?? [])

function iconOf(transition: ProductTransition): string {
  return TRANSITIONS.find(entry => entry.name === transition)?.icon ?? 'i-lucide-arrow-right'
}

function ratioColor(ratio: number): 'success' | 'warning' | 'error' {
  return ratio === 100 ? 'success' : ratio >= 50 ? 'warning' : 'error'
}

async function transition(name: ProductTransition): Promise<void> {
  const note = comment.value.trim()

  if (client && await action.run(async () => showroomData(await client.POST('/products/{product}/transitions', {
    params: { path: { product: props.product.id } },
    body: { transition: name, ...(note ? { comment: note } : {}) }
  })))) {
    comment.value = ''
    emit('changed')
  }
}

async function revert(version: ShowroomVersion): Promise<void> {
  if (!client || !window.confirm(t('showroom.catalog.workflow.confirm_revert', { version: version.version }))) {
    return
  }

  if (await action.run(async () => showroomData(await client.POST('/products/{product}/revert', {
    params: { path: { product: props.product.id } },
    body: { version: version.version }
  })))) {
    emit('changed')
  }
}
</script>

<template>
  <div class="space-y-5">
    <UCard data-testid="showroom-workflow">
      <template #header>
        <h2 class="font-semibold">{{ t('showroom.catalog.workflow.title') }}</h2>
      </template>

      <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3 text-sm">
          <UBadge :color="statusColor(props.product.status)" variant="subtle" data-testid="showroom-product-status">{{ t(`showroom.statuses.${props.product.status}`) }}</UBadge>
          <span data-testid="showroom-published-version">
            {{ t('showroom.catalog.workflow.published_version') }}:
            <template v-if="props.product.published_version">
              <span class="font-mono">v{{ props.product.published_version }}</span>
              <span class="text-muted"> {{ formatDate(props.product.published_at) }}</span>
            </template>
            <span v-else class="text-muted">{{ t('showroom.catalog.workflow.not_published') }}</span>
          </span>
        </div>

        <CatalogActionError :action="action" />

        <form v-if="transitions.length > 0" class="flex flex-wrap items-end gap-3" data-testid="showroom-transition-form" @submit.prevent>
          <UFormField :label="t('showroom.catalog.workflow.comment')" :error="action.error('comment')">
            <UInput v-model="comment" class="w-72" data-testid="showroom-transition-comment" />
          </UFormField>
          <UButton
            v-for="name in transitions"
            :key="name"
            :icon="iconOf(name)"
            :variant="name === 'reject' || name === 'archive' ? 'outline' : 'solid'"
            :loading="action.busy.value"
            :data-testid="`showroom-transition-${name}`"
            @click="transition(name)"
          >
            {{ t(`showroom.catalog.workflow.transitions.${name}`) }}
          </UButton>
        </form>
        <small v-if="action.error('transition')" class="text-xs text-error">{{ action.error('transition') }}</small>
      </div>
    </UCard>

    <UCard data-testid="showroom-completeness">
      <template #header>
        <h2 class="font-semibold">{{ t('showroom.catalog.workflow.completeness') }}</h2>
      </template>
      <p v-if="completeness.length === 0" class="text-sm text-muted">{{ t('showroom.catalog.workflow.no_completeness') }}</p>
      <table v-else class="w-full text-left text-sm">
        <thead class="text-muted">
          <tr>
            <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.fields.channel') }}</th>
            <th class="py-1 pr-3 font-medium">{{ t('showroom.catalog.fields.locale') }}</th>
            <th class="py-1 pr-3 font-medium">%</th>
            <th class="py-1 font-medium">{{ t('showroom.catalog.workflow.missing') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-default">
          <tr v-for="score in completeness" :key="`${score.scope}/${score.locale}`" :data-completeness="`${score.scope}/${score.locale}`">
            <td class="py-1 pr-3 font-mono text-xs">{{ score.scope }}</td>
            <td class="py-1 pr-3 font-mono text-xs">{{ score.locale }}</td>
            <td class="py-1 pr-3"><UBadge :color="ratioColor(score.ratio)" variant="subtle">{{ score.ratio }}%</UBadge></td>
            <td class="py-1 font-mono text-xs">{{ score.missing_attributes.join(', ') || t('showroom.catalog.none') }}</td>
          </tr>
        </tbody>
      </table>
    </UCard>

    <UCard data-testid="showroom-history">
      <template #header>
        <h2 class="font-semibold">{{ t('showroom.catalog.workflow.history') }}</h2>
      </template>
      <p v-if="props.versions.length === 0" class="text-sm text-muted">{{ t('showroom.catalog.workflow.no_history') }}</p>
      <ol v-else class="flex flex-col gap-3">
        <li
          v-for="(version, index) in props.versions"
          :key="version.version"
          class="flex flex-wrap items-start justify-between gap-3 border-b border-default pb-2 text-sm last:border-0"
          :data-testid="`showroom-version-${version.version}`"
        >
          <div>
            <p class="flex flex-wrap items-center gap-2">
              <span class="font-mono">v{{ version.version }}</span>
              <UBadge color="neutral" variant="subtle">{{ version.action }}</UBadge>
              <span class="text-muted">{{ formatDate(version.created_at) }}</span>
              <span v-if="version.author" class="text-muted">{{ t('showroom.catalog.workflow.by', { author: version.author.id }) }}</span>
            </p>
            <p v-if="version.comment" class="mt-1 italic">{{ version.comment }}</p>
            <p v-if="Object.keys(version.changes).length > 0" class="mt-1 font-mono text-xs text-muted">{{ Object.keys(version.changes).join(', ') }}</p>
          </div>
          <UButton
            v-if="index > 0"
            size="sm"
            variant="ghost"
            icon="i-lucide-undo-2"
            :loading="action.busy.value"
            :data-testid="`showroom-revert-${version.version}`"
            @click="revert(version)"
          >
            {{ t('showroom.catalog.workflow.revert') }}
          </UButton>
        </li>
      </ol>
    </UCard>
  </div>
</template>
