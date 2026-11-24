<script setup lang="ts">
import {
  productModelPath,
  productSearchPath,
  useCatalogAction,
  type AssociationGroups,
  type AssociationKind,
  type QuantifiedGroups,
  type ShowroomAssociationType
} from './catalog'
import CatalogActionError from './CatalogActionError.vue'

/**
 * A record's associations, own and inherited (v1's `partials/associations`),
 * with one-at-a-time add and remove. Inherited ones are shown but removed on
 * the model they come from. v1's `associations.add` and `.remove` routes have
 * no v2 match; each is sent as an update of the record (`save`), replacing
 * that type's own targets, which is how Showroom writes them.
 */
const props = defineProps<{
  associations: Record<string, AssociationGroups>
  quantified: Record<string, QuantifiedGroups>
  ownAssociations: Record<string, AssociationGroups>
  ownQuantified: Record<string, QuantifiedGroups>
  types: ShowroomAssociationType[]
  save: (body: Record<string, unknown>) => Promise<unknown>
}>()

const emit = defineEmits<{ changed: [] }>()

const { t } = useI18n()
const action = useCatalogAction()

const KINDS: readonly AssociationKind[] = ['products', 'product_models']

const form = reactive({ type: '', kind: 'products' as AssociationKind, target: '', quantity: '' as string | number })

const typeItems = computed(() => props.types.map(type => ({ label: type.code, value: type.code })))

const kindItems = computed(() => [
  { label: t('showroom.catalog.associations.product'), value: 'products' },
  { label: t('showroom.catalog.associations.product_model'), value: 'product_models' }
])

interface Row {
  type: string
  kind: AssociationKind
  identifier: string
  quantity: number | null
  own: boolean
}

const rows = computed(() => {
  const grouped = new Map<string, Row[]>()

  for (const [type, groups] of Object.entries(props.associations)) {
    for (const kind of KINDS) {
      for (const identifier of groups[kind]) {
        const own = props.ownAssociations[type]?.[kind].includes(identifier) ?? false
        grouped.set(type, [...grouped.get(type) ?? [], { type, kind, identifier, quantity: null, own }])
      }
    }
  }

  for (const [type, groups] of Object.entries(props.quantified)) {
    for (const kind of KINDS) {
      for (const target of groups[kind]) {
        const own = props.ownQuantified[type]?.[kind].some(entry => entry.identifier === target.identifier) ?? false
        grouped.set(type, [...grouped.get(type) ?? [], { type, kind, identifier: target.identifier, quantity: target.quantity, own }])
      }
    }
  }

  return [...grouped.entries()].filter(([, entries]) => entries.length > 0)
})

function isQuantified(type: string): boolean {
  return props.types.find(entry => entry.code === type)?.is_quantified ?? type in props.quantified
}

function pathOf(row: Row): string {
  return row.kind === 'products' ? productSearchPath(row.identifier) : productModelPath(row.identifier)
}

/** The body that sets `type`'s own targets to `groups`. */
function bodyFor(type: string, groups: AssociationGroups | QuantifiedGroups): Record<string, unknown> {
  return isQuantified(type) ? { quantified_associations: { [type]: groups } } : { associations: { [type]: groups } }
}

async function send(body: Record<string, unknown>): Promise<void> {
  if (await action.run(() => props.save(body))) {
    form.target = ''
    form.quantity = ''
    emit('changed')
  }
}

async function remove(row: Row): Promise<void> {
  if (isQuantified(row.type)) {
    const own = props.ownQuantified[row.type] ?? { products: [], product_models: [] }

    await send(bodyFor(row.type, { ...own, [row.kind]: own[row.kind].filter(entry => entry.identifier !== row.identifier) }))
  } else {
    const own = props.ownAssociations[row.type] ?? { products: [], product_models: [] }

    await send(bodyFor(row.type, { ...own, [row.kind]: own[row.kind].filter(entry => entry !== row.identifier) }))
  }
}

async function add(): Promise<void> {
  const target = form.target.trim()

  if (!form.type || target === '') {
    return
  }

  if (isQuantified(form.type)) {
    const own = props.ownQuantified[form.type] ?? { products: [], product_models: [] }
    const quantity = Number(form.quantity) >= 1 ? Math.trunc(Number(form.quantity)) : 1

    await send(bodyFor(form.type, { ...own, [form.kind]: [...own[form.kind].filter(entry => entry.identifier !== target), { identifier: target, quantity }] }))
  } else {
    const own = props.ownAssociations[form.type] ?? { products: [], product_models: [] }

    await send(bodyFor(form.type, { ...own, [form.kind]: [...own[form.kind].filter(entry => entry !== target), target] }))
  }
}
</script>

<template>
  <UCard data-testid="showroom-associations">
    <template #header>
      <h2 class="font-semibold">{{ t('showroom.catalog.associations.title') }}</h2>
    </template>

    <div class="space-y-4">
      <AtriumState v-if="rows.length === 0" compact :title="t('showroom.catalog.associations.none')" data-testid="showroom-associations-empty" />
      <div v-for="[type, entries] in rows" :key="type" :data-association-type="type">
        <p class="mb-1 font-mono text-xs font-medium uppercase text-muted">{{ type }}</p>
        <ul class="flex flex-wrap gap-2">
          <li
            v-for="row in entries"
            :key="`${row.kind}-${row.identifier}`"
            class="flex items-center gap-1.5 rounded-md border border-default px-2 py-1 text-sm"
            :data-testid="`showroom-association-${type}-${row.identifier}`"
          >
            <ULink :to="pathOf(row)" class="font-mono hover:underline">{{ row.identifier }}</ULink>
            <span v-if="row.quantity !== null" class="text-xs text-muted">× {{ row.quantity }}</span>
            <UButton
              v-if="row.own"
              size="xs"
              variant="ghost"
              icon="i-lucide-x"
              :aria-label="t('showroom.catalog.associations.remove')"
              :loading="action.busy.value"
              :data-testid="`showroom-association-remove-${type}-${row.identifier}`"
              @click="remove(row)"
            />
            <span v-else class="text-xs text-muted">{{ t('showroom.catalog.inherited') }}</span>
          </li>
        </ul>
      </div>

      <CatalogActionError :action="action" />

      <form v-if="props.types.length > 0" class="flex flex-wrap items-end gap-3" data-testid="showroom-association-add" @submit.prevent="add">
        <UFormField :label="t('showroom.catalog.associations.type')" :error="action.error('associations') ?? action.error('quantified_associations')">
          <USelect v-model="form.type" :items="typeItems" :placeholder="t('showroom.catalog.associations.choose_type')" required class="w-44" data-testid="showroom-association-type" />
        </UFormField>
        <UFormField :label="t('showroom.catalog.associations.target_kind')">
          <USelect v-model="form.kind" :items="kindItems" class="w-40" data-testid="showroom-association-kind" />
        </UFormField>
        <UFormField :label="t('showroom.catalog.associations.target')">
          <UInput v-model="form.target" required class="w-44" data-testid="showroom-association-target" />
        </UFormField>
        <UFormField :label="t('showroom.catalog.associations.quantity')" :hint="t('showroom.catalog.associations.quantity_hint')">
          <UInput v-model="form.quantity" type="number" min="1" class="w-28" data-testid="showroom-association-quantity" />
        </UFormField>
        <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="showroom-association-add-submit">{{ t('showroom.catalog.associations.add') }}</UButton>
      </form>
    </div>
  </UCard>
</template>
