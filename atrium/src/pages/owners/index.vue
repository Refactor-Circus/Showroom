<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  ALL,
  blankToNull,
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  usePickList,
  type CatalogList
} from '../../components/catalog/catalog'
import { newLabels } from '../../components/structure/structure'
import { ownerPath, type ShowroomOwner, type ShowroomOwnerType } from '../../components/owners/owners'

/**
 * `showroom.owners`: every owner (`owners.index`), a cursor page at a time,
 * ported from v1's `owners/index`, with its type, parent and children
 * counted. The filters (search and type) go in the route's query and on to
 * the endpoint's; the owner type page links here with `?type=`. A new owner
 * is created (`owners.store`) with its code, type, parent code and its label
 * in the current locale, once an owner type exists.
 */
const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useCatalogAction()

const query = computed(() => {
  const filters: Record<string, string> = {}

  for (const key of ['search', 'type', 'cursor'] as const) {
    const value = queryValue(route.query[key])

    if (value) {
      filters[key] = value
    }
  }

  return filters
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomOwner>>(await showroom.GET('/owners', { params: { query: untyped(query.value) } })), query)

const owners = computed(() => data.value?.data ?? [])

const types = usePickList(async showroom => showroomData<CatalogList<ShowroomOwnerType>>(await showroom.GET('/owner-types', { params: { query: { per_page: ALL } } })))

const filters = reactive({ search: '', type: '' })

watch(() => route.query, () => {
  filters.search = queryValue(route.query.search) ?? ''
  filters.type = queryValue(route.query.type) ?? ''
}, { immediate: true })

function applyFilters(): void {
  router.replace({
    query: {
      ...(filters.search.trim() ? { search: filters.search.trim() } : {}),
      ...(filters.type ? { type: filters.type } : {})
    }
  })
}

function clearFilters(): void {
  router.replace({ query: {} })
}

const draft = reactive({ code: '', type: '', parent: '', label: '' })

async function create(): Promise<void> {
  if (!client) {
    return
  }

  const parent = blankToNull(draft.parent)
  const body = {
    code: draft.code.trim(),
    type: draft.type,
    ...(parent ? { parent } : {}),
    ...newLabels(locale.value, draft.label)
  }

  if (await action.run(async () => showroomData(await client.POST('/owners', { body: untyped(body) })))) {
    draft.code = ''
    draft.parent = ''
    draft.label = ''
    await reload()
  }
}

function typeLabel(code: string | undefined): string {
  const type = types.value.find(entry => entry.code === code)

  return type ? labelOf(type.labels, locale.value, type.code) : code ?? t('showroom.catalog.none')
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-owners-page">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold">{{ t('showroom.owners.title') }}</h1>
        <p class="text-sm text-muted">{{ t('showroom.owners.description') }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <UButton to="/showroom/owner-types" variant="outline" icon="i-lucide-shapes" data-testid="showroom-owners-owner-types">{{ t('showroom.owner_types.title') }}</UButton>
        <UButton to="/showroom/flags" variant="outline" icon="i-lucide-flag" data-testid="showroom-owners-flags">{{ t('showroom.owners.flags') }}</UButton>
      </div>
    </div>

    <AtriumIntegrationScreen screen-key="showroom.owners">
      <div class="space-y-4">
        <UCard data-testid="showroom-owner-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.owners.new') }}</h2>
          </template>
          <p v-if="types.length === 0" class="text-sm text-muted" data-testid="showroom-owners-no-types">{{ t('showroom.owners.no_types') }}</p>
          <form v-else class="flex flex-col gap-3" data-testid="showroom-owner-create-form" @submit.prevent="create">
            <CatalogActionError :action="action" />
            <div class="flex flex-wrap items-end gap-3">
              <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.owners.code_hint')" :error="action.error('code')" required>
                <UInput v-model="draft.code" required class="w-48" data-testid="showroom-owner-create-code" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.type')" :error="action.error('type')" required>
                <select v-model="draft.type" required class="w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-owner-create-type">
                  <option value="" disabled>{{ t('showroom.owners.choose_type') }}</option>
                  <option v-for="type in types" :key="type.code" :value="type.code">{{ labelOf(type.labels, locale, type.code) }}</option>
                </select>
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.parent')" :hint="t('showroom.owners.parent_hint')" :error="action.error('parent')">
                <UInput v-model="draft.parent" class="w-44" data-testid="showroom-owner-create-parent" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="draft.label" class="w-48" data-testid="showroom-owner-create-label" />
              </UFormField>
              <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-owner-create-submit">{{ t('showroom.catalog.create') }}</UButton>
            </div>
          </form>
        </UCard>

        <UCard>
          <form class="atrium-querybar" data-testid="showroom-owners-filters" @submit.prevent="applyFilters">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="filters.search" class="w-56" data-testid="showroom-owners-search" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.type')">
              <select v-model="filters.type" class="w-44 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="showroom-owners-type">
                <option value="">{{ t('showroom.owners.all_types') }}</option>
                <option v-for="type in types" :key="type.code" :value="type.code">{{ labelOf(type.labels, locale, type.code) }}</option>
              </select>
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" color="neutral" icon="i-lucide-x" @click="clearFilters">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <p v-if="owners.length === 0" class="atrium-empty" data-testid="showroom-owners-empty">{{ t('showroom.owners.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-owners-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.type') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.parent') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.owners.children') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.catalog.fields.enabled') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="owner in owners" :key="owner.id" :data-testid="`showroom-owner-row-${owner.code}`">
                  <td class="py-2 pr-3" :style="{ paddingLeft: `${owner.depth * 0.75}rem` }">
                    <RouterLink :to="ownerPath(owner.id)" class="font-mono font-medium hover:underline">{{ owner.code }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3">{{ labelOf(owner.labels, locale, owner.code) }}</td>
                  <td class="py-2 pr-3" :title="typeLabel(owner.type)">
                    <span class="font-mono text-xs">{{ owner.type ?? t('showroom.catalog.none') }}</span>
                  </td>
                  <td class="py-2 pr-3 font-mono text-xs">{{ owner.parent ?? t('showroom.catalog.none') }}</td>
                  <td class="py-2 pr-3 tabular-nums">{{ owner.children_count ?? 0 }}</td>
                  <td class="py-2">
                    <UBadge :color="owner.enabled ? 'success' : 'neutral'" variant="subtle">
                      {{ owner.enabled ? t('showroom.catalog.enabled') : t('showroom.catalog.disabled') }}
                    </UBadge>
                  </td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-owners" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
