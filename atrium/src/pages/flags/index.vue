<script setup lang="ts">
import CatalogActionError from '../../components/catalog/CatalogActionError.vue'
import CatalogPager from '../../components/catalog/CatalogPager.vue'
import CatalogState from '../../components/catalog/CatalogState.vue'
import {
  labelOf,
  queryValue,
  showroomData,
  untyped,
  useCatalogAction,
  useCatalogLoad,
  type CatalogList,
  type ShowroomFlag
} from '../../components/catalog/catalog'
import FlagFields from '../../components/structure/FlagFields.vue'
import { FLAG_SUBJECTS, flagPath, newLabels, sortOrderField, type FlagForm } from '../../components/structure/structure'

/**
 * `showroom.flags`: every flag definition (`flags.index`), a cursor page at a
 * time, ported from v1's `flags/index`. A new flag is created with its code,
 * its label in the current locale, what it applies to (both subjects ticked,
 * as v1 offered it) and its sort order, left to Showroom when blank
 * (`flags.store`).
 */
const { t, locale } = useI18n()
const route = useRoute()
const action = useCatalogAction()

const query = computed(() => {
  const cursor = queryValue(route.query.cursor)

  return cursor ? { cursor } : {}
})

const { client, data, pending, failed, status, reload } = useCatalogLoad(async showroom =>
  showroomData<CatalogList<ShowroomFlag>>(await showroom.GET('/flags', { params: { query: query.value } })), query)

const flags = computed(() => data.value?.data ?? [])
const draft = reactive({ code: '', label: '' })

function blankFields(): FlagForm {
  return { applies_to: [...FLAG_SUBJECTS], sort_order: '' }
}

const fields = ref<FlagForm>(blankFields())

async function create(): Promise<void> {
  const body = {
    code: draft.code.trim(),
    ...newLabels(locale.value, draft.label),
    applies_to: [...fields.value.applies_to],
    ...sortOrderField(fields.value.sort_order)
  }

  if (client && await action.run(async () => showroomData(await client.POST('/flags', { body: untyped(body) })))) {
    draft.code = ''
    draft.label = ''
    fields.value = blankFields()
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="showroom-flags">
    <div>
      <h1 class="text-xl font-semibold">{{ t('showroom.structure.flags.title') }}</h1>
      <p class="text-sm text-muted">{{ t('showroom.structure.flags.description') }}</p>
    </div>

    <AtriumIntegrationScreen screen-key="showroom.flags">
      <div class="space-y-4">
        <UCard data-testid="showroom-flag-new">
          <template #header>
            <h2 class="font-semibold">{{ t('showroom.structure.flags.new') }}</h2>
          </template>
          <form class="flex flex-col gap-4" data-testid="showroom-flag-create-form" @submit.prevent="create">
            <div class="flex flex-wrap items-end gap-3">
              <UFormField :label="t('showroom.catalog.fields.code')" :hint="t('showroom.structure.code_hint')" :error="action.error('code')" required>
                <UInput v-model="draft.code" required class="w-56" data-testid="showroom-flag-create-code" />
              </UFormField>
              <UFormField :label="t('showroom.catalog.fields.label_in', { locale })" :error="action.error('labels')">
                <UInput v-model="draft.label" class="w-56" data-testid="showroom-flag-create-label" />
              </UFormField>
            </div>
            <FlagFields v-model="fields" :action="action" testid="showroom-flag-create" />
            <div>
              <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-flag-create-submit">{{ t('showroom.catalog.create') }}</UButton>
            </div>
          </form>
          <CatalogActionError :action="action" class="mt-3" />
        </UCard>

        <CatalogState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <p v-if="flags.length === 0" class="atrium-empty" data-testid="showroom-flags-empty">{{ t('showroom.structure.flags.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="showroom-flags-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.code') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.catalog.fields.label') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('showroom.structure.flags.applies_to') }}</th>
                  <th class="py-2 font-medium">{{ t('showroom.catalog.fields.sort_order') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="flag in flags" :key="flag.code" :data-testid="`showroom-flag-row-${flag.code}`">
                  <td class="py-2 pr-3">
                    <RouterLink :to="flagPath(flag.code)" class="font-mono font-medium hover:underline">{{ flag.code }}</RouterLink>
                  </td>
                  <td class="py-2 pr-3">{{ labelOf(flag.labels, locale, flag.code) }}</td>
                  <td class="py-2 pr-3">
                    <span class="flex flex-wrap gap-1">
                      <UBadge v-for="subject in flag.applies_to" :key="subject" color="neutral" variant="outline">{{ t(`showroom.structure.flags.subject_${subject}`) }}</UBadge>
                    </span>
                  </td>
                  <td class="py-2 tabular-nums">{{ flag.sort_order }}</td>
                </tr>
              </tbody>
            </table>

            <CatalogPager :next="data?.meta?.next_cursor ?? null" :prev="data?.meta?.prev_cursor ?? null" testid="showroom-flags" />
          </div>
        </CatalogState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
