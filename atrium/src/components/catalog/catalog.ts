import { ref, shallowRef, watch } from 'vue'
import type { Ref, ShallowRef, WatchSource } from 'vue'
import { AtriumIntegrationError, useAtriumIntegration, type AtriumIntegrationClient } from '@refactor-circus/atrium'
import { atriumStatus } from '@refactor-circus/atrium'

/*
 * What the Showroom catalog pages share (AC 54, 71): products, product models,
 * families and family variants. The payload types, the client, a loader, the
 * list paging (page numbers for products, a cursor for the rest), an action
 * runner (field errors on a 422, no access on a 403), and the value form.
 *
 * Scramble types most of Showroom's resources loosely in the generated
 * `client/src/schema.d.ts` (a product list as `string[]`, `values` as a string, a
 * version as `unknown[]`), so the shapes are declared here as Showroom's
 * resources build them; the gaps are Showroom's to fix in its `schema()`
 * declarations.
 */

export type ShowroomClient = AtriumIntegrationClient<'showroom'>

/** Where a product's working copy stands in review. */
export type ProductStatus = 'draft' | 'in_review' | 'approved' | 'archived'

/** Every product status, in workflow order. */
export const PRODUCT_STATUSES: readonly ProductStatus[] = ['draft', 'in_review', 'approved', 'archived']

/** A step through the product workflow (`products.transitions.store`). */
export type ProductTransition = 'submit' | 'approve' | 'reject' | 'publish' | 'unpublish' | 'archive' | 'restore'

/** Every transition, in Showroom's order, with the statuses it starts from (`require_approval` on). */
export const TRANSITIONS: readonly { name: ProductTransition, from: readonly ProductStatus[], icon: string }[] = [
  { name: 'submit', from: ['draft'], icon: 'i-lucide-send' },
  { name: 'approve', from: ['in_review'], icon: 'i-lucide-circle-check' },
  { name: 'reject', from: ['in_review'], icon: 'i-lucide-circle-x' },
  { name: 'publish', from: ['approved'], icon: 'i-lucide-rocket' },
  { name: 'unpublish', from: ['draft', 'in_review', 'approved'], icon: 'i-lucide-eye-off' },
  { name: 'archive', from: ['draft', 'in_review', 'approved'], icon: 'i-lucide-archive' },
  { name: 'restore', from: ['archived'], icon: 'i-lucide-archive-restore' }
]

/** The transitions a product may take, as v1 offered them: unpublish only while published. */
export function availableTransitions(status: ProductStatus, publishedVersion: number | null): ProductTransition[] {
  return TRANSITIONS
    .filter(transition => transition.from.includes(status) && !(transition.name === 'unpublish' && publishedVersion === null))
    .map(transition => transition.name)
}

/** The badge colour of a product status, as v1's `Badges::forStatus()` gave it. */
export function statusColor(status: string | null | undefined): 'primary' | 'success' | 'info' | 'neutral' {
  switch (status) {
    case 'in_review':
      return 'info'
    case 'approved':
      return 'success'
    case 'draft':
      return 'primary'
    default:
      return 'neutral'
  }
}

export type AttributeType = 'text' | 'textarea' | 'number' | 'decimal' | 'boolean' | 'date' | 'select' | 'multiselect' | 'price' | 'metric'

/** One value slot of an attribute: a locale, a channel (`scope`) and its data. */
export interface ValueSlot {
  locale: string | null
  scope: string | null
  data: unknown
}

/** Values in Showroom's standard shape, keyed by attribute code. */
export type CatalogValues = Record<string, ValueSlot[]>

/** A linked asset, own or inherited (`LinkedAssets::present()`). */
export interface CatalogAsset {
  code: string
  role: string | null
  sort_order: number
  url: string | null
  mime_type: string | null
  inherited: boolean
}

/** The targets of one association type. */
export interface AssociationGroups {
  products: string[]
  product_models: string[]
}

export interface QuantifiedTarget {
  identifier: string
  quantity: number
}

/** The targets of one quantified association type. */
export interface QuantifiedGroups {
  products: QuantifiedTarget[]
  product_models: QuantifiedTarget[]
}

export interface CompletenessScore {
  scope: string
  locale: string
  required: number
  missing: number
  ratio: number
  missing_attributes: string[]
}

/** `ProductResource`, as `products.show` loads it. */
export interface ShowroomProduct {
  id: string
  identifier: string
  slug: string
  family?: string | null
  parent?: string | null
  owner: string | null
  categories: string[]
  assets?: CatalogAsset[]
  enabled: boolean
  effective_enabled?: boolean | null
  status: ProductStatus
  published_version: number | null
  published_at: string | null
  completeness?: CompletenessScore[]
  values: CatalogValues
  associations?: Record<string, AssociationGroups>
  quantified_associations?: Record<string, QuantifiedGroups>
  external_ids?: Record<string, string>
  external_ids_synced_at?: Record<string, string | null>
  flags?: string[]
  effective_flags?: string[] | null
  created_at: string | null
  updated_at: string | null
  changed_at: string | null
}

/** `ProductModelResource`. */
export interface ShowroomProductModel {
  id: string
  code: string
  family_variant?: string
  parent?: string | null
  owner: string | null
  categories: string[]
  assets?: CatalogAsset[]
  level: number
  values: CatalogValues
  children?: string[]
  products?: string[]
  associations?: Record<string, AssociationGroups>
  quantified_associations?: Record<string, QuantifiedGroups>
  created_at: string | null
  updated_at: string | null
}

/** `FamilyAttributeResource`: an attribute's membership of a family. */
export interface ShowroomFamilyAttribute {
  attribute: string
  type: AttributeType
  group?: string | null
  is_required: boolean
  required_channels: string[] | null
  sort_order: number
}

/** `FamilyResource`. */
export interface ShowroomFamily {
  id: string
  code: string
  labels: Record<string, string | null>
  label_attribute?: string | null
  sort_order: number
  attributes_count?: number
  attributes?: ShowroomFamilyAttribute[]
  created_at: string | null
  updated_at: string | null
}

/** One level of a family variant: the axes that tell its records apart, and the attributes set there. */
export interface FamilyVariantLevel {
  level: number
  axes: string[]
  attributes: string[]
}

/** `FamilyVariantResource`. */
export interface ShowroomFamilyVariant {
  id: string
  code: string
  family?: string
  labels: Record<string, string | null>
  levels?: FamilyVariantLevel[]
  common_attributes?: string[]
  product_models_count?: number
  created_at: string | null
  updated_at: string | null
}

/** `AttributeResource`. */
export interface ShowroomAttribute {
  id: string
  code: string
  type: AttributeType
  group?: string | null
  labels: Record<string, string | null>
  is_unique: boolean
  is_localizable: boolean
  is_scopable: boolean
  settings: Record<string, unknown>
  sort_order: number
  options?: ShowroomAttributeOption[]
}

/** `AttributeOptionResource`. */
export interface ShowroomAttributeOption {
  id: string
  code: string
  labels: Record<string, string | null>
  sort_order: number
}

/** `ChannelResource`. */
export interface ShowroomChannel {
  id: string
  code: string
  labels: Record<string, string | null>
  locales?: string[]
  currencies: string[]
}

/** `LocaleResource`. */
export interface ShowroomLocale {
  id: string
  code: string
  labels: Record<string, string | null>
}

/** `FlagResource`. */
export interface ShowroomFlag {
  id: string
  code: string
  labels: Record<string, string | null>
  applies_to: string[]
  sort_order: number
}

/** `AssociationTypeResource`. */
export interface ShowroomAssociationType {
  id: string
  code: string
  labels: Record<string, string | null>
  is_two_way: boolean
  is_quantified: boolean
}

/** `VersionSummaryResource`: one entry of a product's history. */
export interface ShowroomVersion {
  version: number
  action: string
  comment: string | null
  author: { type: string, id: string | number } | null
  changes: Record<string, unknown>
  created_at: string | null
}

/** A list answer: numbered pages (product search) or a cursor (every other list). */
export interface CatalogList<T> {
  data: T[]
  meta?: {
    current_page?: number
    last_page?: number
    next_cursor?: string | null
    prev_cursor?: string | null
  }
}

/** One record under `data`. */
export interface CatalogOne<T> {
  data: T
}

/** The most a Showroom list answers in one page (`showroom.pagination.max_per_page`). */
export const ALL = 100

/** The Showroom client, signed by the app's auth adapter; null while Showroom is not active. */
export function useShowroom(): ShowroomClient | null {
  return useAtriumIntegration('showroom').client
}

/**
 * The data of an `openapi-fetch` answer as `T`, or an `AtriumIntegrationError`
 * carrying its status and body.
 */
export function showroomData<T>(result: { data?: unknown, error?: unknown, response: Response }): T {
  if (!result.response.ok) {
    throw new AtriumIntegrationError(result.response.status, result.error)
  }

  return result.data as T
}

/**
 * A query or body the generated schema types differently from what Showroom
 * validates (`values` as `string[]`, `external[]` as required), sent as
 * Showroom's rules take it.
 */
export function untyped<T>(value: T): never {
  return value as never
}

export interface CatalogLoad<T> {
  client: ShowroomClient | null
  data: ShallowRef<T | null>
  /** True only while there is nothing to show yet, so a reload keeps forms mounted */
  pending: Ref<boolean>
  failed: Ref<boolean>
  /** The HTTP status of a failed load: 403 shows no access, 404 not found */
  status: Ref<number | null>
  reload: () => Promise<void>
}

/** Loads what a page shows from Showroom, and again on `reload()` or whenever `source` changes. */
export function useCatalogLoad<T>(load: (client: ShowroomClient) => Promise<T>, source?: WatchSource): CatalogLoad<T> {
  const client = useShowroom()
  const data = shallowRef<T | null>(null)
  const pending = ref(true)
  const failed = ref(false)
  const status = ref<number | null>(null)

  async function reload(): Promise<void> {
    if (!client) {
      pending.value = false
      return
    }

    pending.value = data.value === null

    try {
      data.value = await load(client)
      failed.value = false
      status.value = null
    } catch (error) {
      failed.value = true
      status.value = atriumStatus(error)
    } finally {
      pending.value = false
    }
  }

  if (source) {
    watch(source, () => void reload(), { deep: true })
  }

  void reload()

  return { client, data, pending, failed, status, reload }
}

/**
 * A list a form picks from (families, attributes, channels…): loaded once, at
 * most `ALL` records. A failure leaves it empty: the form still works.
 */
export function usePickList<T>(load: (client: ShowroomClient) => Promise<CatalogList<T>>): ShallowRef<T[]> {
  const client = useShowroom()
  const list = shallowRef<T[]>([])

  if (client) {
    load(client)
      .then((answer) => {
        list.value = answer.data
      })
      .catch(() => {
        list.value = []
      })
  }

  return list
}

export interface CatalogAction {
  busy: Ref<boolean>
  /** The field errors of the last 422, by field */
  errors: Ref<Record<string, string[]>>
  /** The last action answered 403 */
  forbidden: Ref<boolean>
  /** Why the last action failed, other than a 422 or 403 */
  message: Ref<string | null>
  /** Runs `action`; resolves whether it succeeded, so the page reloads only then */
  run: (action: () => Promise<unknown>) => Promise<boolean>
  /** The first error of `field`, or (unless `exact`) of any field under it (`values.name.0.data`) */
  error: (field: string, exact?: boolean) => string | undefined
}

/**
 * Runs a mutation against Showroom. A 422 fills `errors` (by field), a 403
 * sets `forbidden`, and any other failure (a 409 workflow refusal among them)
 * sets `message`.
 */
export function useCatalogAction(): CatalogAction {
  const busy = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const forbidden = ref(false)
  const message = ref<string | null>(null)

  async function run(action: () => Promise<unknown>): Promise<boolean> {
    busy.value = true
    errors.value = {}
    forbidden.value = false
    message.value = null

    try {
      await action()
      return true
    } catch (error) {
      const status = atriumStatus(error)
      const body = (error instanceof AtriumIntegrationError ? error.body : null) as { message?: unknown, errors?: Record<string, string[]> } | null

      if (status === 403) {
        forbidden.value = true
      } else if (status === 422 && body?.errors) {
        errors.value = body.errors
      } else {
        message.value = typeof body?.message === 'string' ? body.message : error instanceof Error ? error.message : String(error)
      }

      return false
    } finally {
      busy.value = false
    }
  }

  function error(field: string, exact = false): string | undefined {
    const own = errors.value[field]?.[0]

    if (own || exact) {
      return own
    }

    const nested = Object.keys(errors.value).find(key => key.startsWith(`${field}.`))

    return nested ? errors.value[nested]?.[0] : undefined
  }

  return { busy, errors, forbidden, message, run, error }
}

/** A route query value as one string, or undefined. */
export function queryValue(value: unknown): string | undefined {
  const first = Array.isArray(value) ? value[0] : value

  return typeof first === 'string' && first !== '' ? first : undefined
}

/** A route query value as a page number (1 when absent or invalid). */
export function queryPage(value: unknown): number {
  const page = Number(queryValue(value))

  return Number.isInteger(page) && page > 1 ? page : 1
}

/** A record's label in `locale`, else its first label, else `fallback`. */
export function labelOf(labels: Record<string, string | null> | null | undefined, locale: string, fallback: string): string {
  const own = labels?.[locale]

  if (own) {
    return own
  }

  return Object.values(labels ?? {}).find((label): label is string => typeof label === 'string' && label !== '') ?? fallback
}

/** A timestamp as the user's locale shows it, or null. */
export function formatDate(value: string | null | undefined): string | null {
  if (!value) {
    return null
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}

const segment = (value: string) => encodeURIComponent(value)

/** The layer path of a product's page, by its id (Showroom's default lookup key). */
export function productPath(id: string): string {
  return `/showroom/products/${segment(id)}`
}

/**
 * Where a product named by its identifier is found: associations and a
 * model's variants name products by identifier, and the product page takes
 * an id, so they link to the product list searched for it.
 */
export function productSearchPath(identifier: string): string {
  return `/showroom/products?search=${segment(identifier)}`
}

/** The layer path of a product model's page, by its code. */
export function productModelPath(code: string): string {
  return `/showroom/product-models/${segment(code)}`
}

/** The layer path of a family's page, by its code. */
export function familyPath(code: string): string {
  return `/showroom/families/${segment(code)}`
}

/** The layer path of a family variant's page, by its code. */
export function familyVariantPath(code: string): string {
  return `/showroom/family-variants/${segment(code)}`
}

/** The layer path of an attribute's page, by its code. */
export function attributePath(code: string): string {
  return `/showroom/attributes/${segment(code)}`
}

/** The layer path of a category's page, by its code. */
export function categoryPath(code: string): string {
  return `/showroom/categories/${segment(code)}`
}

/** Codes typed as a comma-separated list, trimmed, blanks and repeats dropped. */
export function splitCodes(text: string): string[] {
  return [...new Set(text.split(',').map(code => code.trim()).filter(code => code !== ''))]
}

/** A trimmed form value, or null when blank. */
export function blankToNull(value: string | null | undefined): string | null {
  const trimmed = (value ?? '').trim()

  return trimmed === '' ? null : trimmed
}

/** A record's external ids as the form edits them: one input per source held, and one new source and id. */
export interface ExternalIdsForm {
  ids: Record<string, string>
  source: string
  id: string
}

/** The form for a record's `{source: id}` map. */
export function externalIdsForm(ids: Record<string, string> | null | undefined): ExternalIdsForm {
  return { ids: { ...ids }, source: '', id: '' }
}

/**
 * The `external_ids` map a save sends (v1's `ExternalIdForm`): each source
 * held, an emptied one as null (which removes it), plus the new source.
 */
export function externalIdsBody(form: ExternalIdsForm): Record<string, string | null> {
  const map: Record<string, string | null> = {}

  for (const [source, id] of Object.entries(form.ids)) {
    map[source] = blankToNull(id)
  }

  const source = form.source.trim()
  const id = form.id.trim()

  if (source !== '' && id !== '') {
    map[source] = id
  }

  return map
}

/**
 * What a record shows as its label: the value of its family's label
 * attribute, in `locale` when it varies by locale, else its first slot.
 */
export function labelValue(values: CatalogValues | null | undefined, attribute: string | null | undefined, locale: string): string | null {
  const slots = attribute ? values?.[attribute] ?? [] : []
  const slot = slots.find(entry => entry.locale === locale) ?? slots.find(entry => entry.locale === null) ?? slots[0]
  const data = slot?.data

  return typeof data === 'string' || typeof data === 'number' ? String(data) : null
}

/** The data of one slot of `values`, or undefined when it holds none. */
export function slotData(values: CatalogValues | null | undefined, code: string, scope: string | null, locale: string | null): unknown {
  return values?.[code]?.find(slot => slot.scope === scope && slot.locale === locale)?.data
}

function sameJson(a: unknown, b: unknown): boolean {
  return JSON.stringify(a) === JSON.stringify(b)
}

/**
 * A record's own values: what it shows minus what it inherits. Showroom sends
 * a record's values merged with its models' (`presentedValues()`), so a slot
 * the parent holds with the same data is the parent's.
 */
export function ownValues(values: CatalogValues | null | undefined, inherited: CatalogValues | null | undefined): CatalogValues {
  const own: CatalogValues = {}

  for (const [code, slots] of Object.entries(values ?? {})) {
    const kept = slots.filter(slot => !(inherited?.[code] ?? []).some(parent => parent.scope === slot.scope && parent.locale === slot.locale && sameJson(parent.data, slot.data)))

    if (kept.length > 0) {
      own[code] = kept
    }
  }

  return own
}

/** A record's own categories: what it shows minus what its parent model has. */
export function ownCategories(categories: readonly string[], inherited: readonly string[]): string[] {
  return categories.filter(code => !inherited.includes(code))
}

/** The association kinds a record may point at. */
export type AssociationKind = 'products' | 'product_models'

/** A record's own targets of each type: what it shows minus what its parent model has. */
export function ownAssociations(
  shown: Record<string, AssociationGroups> | undefined,
  inherited: Record<string, AssociationGroups> | undefined
): Record<string, AssociationGroups> {
  const own: Record<string, AssociationGroups> = {}

  for (const [type, groups] of Object.entries(shown ?? {})) {
    own[type] = {
      products: groups.products.filter(target => !(inherited?.[type]?.products ?? []).includes(target)),
      product_models: groups.product_models.filter(target => !(inherited?.[type]?.product_models ?? []).includes(target))
    }
  }

  return own
}

/** A record's own quantified targets of each type: what it shows minus what its parent model has with the same quantity. */
export function ownQuantified(
  shown: Record<string, QuantifiedGroups> | undefined,
  inherited: Record<string, QuantifiedGroups> | undefined
): Record<string, QuantifiedGroups> {
  const own: Record<string, QuantifiedGroups> = {}
  const keep = (targets: QuantifiedTarget[], parents: QuantifiedTarget[] | undefined) =>
    targets.filter(target => !(parents ?? []).some(parent => parent.identifier === target.identifier && parent.quantity === target.quantity))

  for (const [type, groups] of Object.entries(shown ?? {})) {
    own[type] = {
      products: keep(groups.products, inherited?.[type]?.products),
      product_models: keep(groups.product_models, inherited?.[type]?.product_models)
    }
  }

  return own
}
