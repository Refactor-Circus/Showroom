<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  blankToNull,
  familyPath,
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogList,
  type ShowroomFamily
} from '../../components/catalog/catalog'

/**
 * `showroom.families`: every family (`families.index`), ported from v1's
 * `families/index`. The search lives in the URL and goes to the endpoint as
 * its query, a cursor page at a time. A new family is created with its code,
 * its label in the current locale and its sort order (`families.store`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useCatalogAction()

const query = computed(() => {
  const search = queryValue(route.query.search)
  const cursor = queryValue(route.query.cursor)

  return { ...(search ? { search } : {}), ...(cursor ? { cursor } : {}) }
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomFamily>>(await showroom.GET('/families', { params: { query: query.value } })), query)

const families = computed(() => data.value?.data ?? [])

const search = ref('')
const draft = reactive({ code: '', label: '', sort_order: '0' })

watch(() => route.query.search, () => {
  search.value = queryValue(route.query.search) ?? ''
}, { immediate: true })

function filter(): void {
  router.replace({ query: { ...route.query, search: search.value || undefined, cursor: undefined } })
}

function clear(): void {
  router.replace({ query: {} })
}

async function create(): Promise<void> {
  const label = blankToNull(draft.label)

  if (client && await action.run(async () => showroomData(await client.POST('/families', {
    body: untyped({
      code: draft.code.trim(),
      ...(label ? { labels: { [locale.value]: label } } : {}),
      sort_order: Number(draft.sort_order) || 0
    })
  })))) {
    draft.code = ''
    draft.label = ''
    draft.sort_order = '0'
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-families">
    <h1 class="text-xl font-semibold">{{ t('showroom.catalog.families.title') }}</h1>

    <AtriumIntegrationScreen screen-key="showroom.families">
      <div class="space-y-4">
        <UCard data-testid="showroom-family-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.catalog.families.new') }}</h2>
          </template>
          <form class="flex flex-wrap items-end gap-3" data-testid="showroom-family-create-form" @submit.prevent="create">
            <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.catalog.families.code_hint')" :error="action.error('code')" required>
              <UInput v-model="draft.code" required class="w-56" data-testid="showroom-family-create-code" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
              <UInput v-model="draft.label" class="w-56" data-testid="showroom-family-create-label" />
            </UFormField>
            <UFormField :label="t('showroom.catalog.fields.sort_order')" :error="action.error('sort_order')">
              <UInput v-model="draft.sort_order" type="number" min="0" class="w-28" data-testid="showroom-family-create-sort" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-family-create-submit">{{ t('showroom.catalog.families.create') }}</UButton>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <UCard>
          <form class="atrium-querybar" data-testid="showroom-families-filters" @submit.prevent="filter">
            <UFormField :label="t('showroom.catalog.filters.search')">
              <UInput v-model="search" class="w-56" data-testid="showroom-families-search" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-filter" data-testid="showroom-families-filter">{{ t('showroom.catalog.filters.filter') }}</UButton>
            <UButton variant="ghost" icon="i-lucide-x" data-testid="showroom-families-clear" @click="clear">{{ t('showroom.catalog.filters.clear') }}</UButton>
          </form>
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <p v-if="families.length === 0" class="atrium-empty" data-testid="showroom-families-empty">{{ t('showroom.catalog.families.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-families-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.families.attributes_count') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.catalog.families.label_attribute') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="family in families" :key="family.code" :data-testid="`showroom-family-row-${family.code}`">
                  <td class="py-2 pr-3">
                    <RouterLink :to="familyPath(family.code)" class="font-mono font-medium hover:underline">{{ family.code }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3">{{ labelOf(family.labels, locale, family.code) }}</td>
                  <td class="py-2 pr-3 tabular-nums">{{ family.attributes_count ?? t('showroom.catalog.none') }}</td>
                  <td class="py-2 font-mono text-xs">{{ family.label_attribute ?? t('showroom.catalog.none') }}</td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-families" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
