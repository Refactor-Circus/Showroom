import {
  ALL,
  blankToNull,
  showroomData,
  splitCodes,
  untyped,
  type AttributeType,
  type CatalogList,
  type CatalogOne,
  type ShowroomAssociationType,
  type ShowroomAttribute,
  type ShowroomChannel,
  type ShowroomClient
} from '../catalog/catalog'

/*
 * What the Showroom structure pages share (AC 54, 71): attributes and their
 * options, attribute groups, association types, categories, channels with
 * their locales, and flags. The generic pieces (the client, the loader, the
 * action runner, paging and the load states) come from `../catalog`; this
 * file adds the payload shapes the catalog does not declare, the form helpers
 * and the layer paths of this area.
 *
 * Scramble types several of these resources loosely in the generated
 * `client/src/schema.d.ts` (a group's `attributes` and a category's `children` as
 * `string[]` or `unknown`, a channel's `locales` as an object), so the shapes
 * are declared as Showroom's resources build them.
 */

/** Every attribute type, in Showroom's order (`AttributeType::cases()`). */
export const ATTRIBUTE_TYPES: readonly AttributeType[] = ['text', 'textarea', 'number', 'decimal', 'boolean', 'date', 'select', 'multiselect', 'price', 'metric']

/** Whether an attribute of `type` has options (`AttributeType::hasOptions()`). */
export function hasOptions(type: AttributeType | string | null | undefined): boolean {
  return type === 'select' || type === 'multiselect'
}

/** The three switches of an attribute, as v1's flags partial offered them. */
export const ATTRIBUTE_FLAGS = ['is_unique', 'is_localizable', 'is_scopable'] as const

export type AttributeFlag = (typeof ATTRIBUTE_FLAGS)[number]

export type AttributeFlags = Record<AttributeFlag, boolean>

/** What a flag may be assigned to (`FlagSubject`). */
export const FLAG_SUBJECTS = ['product', 'owner'] as const

export type FlagSubject = (typeof FLAG_SUBJECTS)[number]

/** `AttributeGroupResource`: `attributes` when loaded (`attribute-groups.show`). */
export interface ShowroomAttributeGroup {
  id: string
  code: string
  labels: Record<string, string | null>
  sort_order: number
  attributes_count?: number
  attributes?: ShowroomAttribute[]
  created_at: string | null
  updated_at: string | null
}

/** `CategoryResource`: what each endpoint loads is optional. */
export interface ShowroomCategory {
  id: string
  code: string
  labels: Record<string, string | null>
  parent?: string | null
  sort_order: number
  depth: number
  children?: string[]
  children_count?: number
  products_count?: number
  product_models_count?: number
  created_at: string | null
  updated_at: string | null
}

/** `ChannelResource`, with the category tree it loads. */
export type StructureChannel = ShowroomChannel & { category_tree?: string | null }

/** `AssociationTypeResource`, with the associations it counts. */
export type StructureAssociationType = ShowroomAssociationType & { associations_count?: number }

/** A channel's create or edit form: currencies typed as a comma-separated list. */
export interface ChannelForm {
  locales: string[]
  currencies: string
  category_tree: string
}

/** The `ChannelForm` of `channel`, or a blank one. */
export function channelForm(channel?: StructureChannel | null): ChannelForm {
  return {
    locales: [...(channel?.locales ?? [])],
    currencies: (channel?.currencies ?? []).join(', '),
    category_tree: channel?.category_tree ?? ''
  }
}

/** What a channel save sends of `form` (v1's `channels/fields`). */
export function channelBody(form: ChannelForm): { locales: string[], currencies: string[], category_tree: string | null } {
  return {
    locales: [...form.locales],
    currencies: splitCodes(form.currencies),
    category_tree: form.category_tree || null
  }
}

/** A flag's create or edit form. */
export interface FlagForm {
  applies_to: string[]
  sort_order: string
}

/** One category of a branch, with the categories filed beneath it. */
export interface CategoryNode {
  category: ShowroomCategory
  children: CategoryNode[]
}

/**
 * The `labels` a create sends: the label typed in `locale`, or nothing when
 * none is typed (Showroom keeps an empty map).
 */
export function newLabels(locale: string, label: string): { labels?: Record<string, string> } {
  const typed = blankToNull(label)

  return typed ? { labels: { [locale]: typed } } : {}
}

/**
 * The `labels` an update sends (v1's `Labels::fromForm()` with the record's
 * labels): every other locale kept, the edited one replaced, or null when
 * emptied.
 */
export function editedLabels(labels: Record<string, string | null> | null | undefined, locale: string, label: string): Record<string, string | null> {
  return { ...labels, [locale]: blankToNull(label) }
}

/** A typed sort order as a number, or undefined when left blank (Showroom's default applies). */
export function sortOrder(value: string | number | null | undefined): number | undefined {
  const text = String(value ?? '').trim()
  const number = Number(text)

  return text === '' || !Number.isFinite(number) ? undefined : number
}

/** `{sort_order}` when one is typed, else nothing, so a create leaves it to Showroom. */
export function sortOrderField(value: string | number | null | undefined): { sort_order?: number } {
  const sort = sortOrder(value)

  return sort === undefined ? {} : { sort_order: sort }
}

/**
 * The `settings` textarea as the object Showroom takes (v1's
 * `AttributeUiController::fromForm()`): blank is `{}`, and anything that is
 * not a JSON object is `null`, which the page refuses before sending.
 */
export function parseSettings(text: string): Record<string, unknown> | null {
  if (text.trim() === '') {
    return {}
  }

  try {
    const decoded: unknown = JSON.parse(text)

    return decoded !== null && typeof decoded === 'object' && !Array.isArray(decoded) ? decoded as Record<string, unknown> : null
  } catch {
    return null
  }
}

/** An attribute's settings as the textarea shows them: pretty JSON, or blank when there are none. */
export function settingsText(settings: Record<string, unknown> | null | undefined): string {
  return settings && Object.keys(settings).length > 0 ? JSON.stringify(settings, null, 2) : ''
}

/**
 * Every record of a cursor-paged list, following `next_cursor` (at most
 * `pages` pages of `ALL`). For a category's branch, which a page shows whole.
 */
export async function everyPage<T>(load: (cursor: string | undefined) => Promise<CatalogList<T>>, pages = 20): Promise<T[]> {
  const records: T[] = []
  let cursor: string | undefined

  for (let page = 0; page < pages; page++) {
    const answer = await load(cursor)
    records.push(...answer.data)
    cursor = answer.meta?.next_cursor ?? undefined

    if (!cursor) {
      break
    }
  }

  return records
}

/** The whole branch beneath `code` (`categories.index?under=`), every page of it. */
export async function loadBranch(client: ShowroomClient, code: string): Promise<ShowroomCategory[]> {
  return everyPage(async cursor => showroomData<CatalogList<ShowroomCategory>>(await client.GET('/categories', {
    params: { query: { under: code, per_page: ALL, ...(cursor ? { cursor } : {}) } }
  })))
}

/**
 * A category's chain, tree root first and the category last (v1's
 * breadcrumb). `categories.show` names only the parent, so each ancestor is
 * loaded in turn, at most `depth` of them.
 */
export async function loadChain(client: ShowroomClient, category: ShowroomCategory): Promise<ShowroomCategory[]> {
  const chain = [category]
  let parent = category.parent ?? null

  for (let step = 0; parent !== null && step < Math.max(category.depth, 0); step++) {
    const code: string = parent
    const ancestor = showroomData<CatalogOne<ShowroomCategory>>(await client.GET('/categories/{category}', { params: { path: { category: code } } })).data
    chain.unshift(ancestor)
    parent = ancestor.parent ?? null
  }

  return chain
}

/** The categories beneath `parent` as a tree, from a flat branch whose entries name their parent. */
export function branchOf(parent: string, categories: readonly ShowroomCategory[]): CategoryNode[] {
  return categories
    .filter(category => category.parent === parent)
    .map(category => ({ category, children: branchOf(category.code, categories) }))
}

/** The tree roots (`categories.index?roots=1`), for a channel's category tree. */
export async function loadTrees(client: ShowroomClient): Promise<CatalogList<ShowroomCategory>> {
  return showroomData<CatalogList<ShowroomCategory>>(await client.GET('/categories', { params: { query: untyped({ roots: 1, per_page: ALL }) } }))
}

const segment = (value: string) => encodeURIComponent(value)

/** The layer path of an attribute group's page, by its code. */
export function attributeGroupPath(code: string): string {
  return `/showroom/attribute-groups/${segment(code)}`
}

/** The layer path of a channel's page, by its code. */
export function channelPath(code: string): string {
  return `/showroom/channels/${segment(code)}`
}

/** The layer path of a flag's page, by its code. */
export function flagPath(code: string): string {
  return `/showroom/flags/${segment(code)}`
}

/** The products filed in a category (v1's "Products" button): the product list filtered by it. */
export function categoryProductsPath(code: string): string {
  return `/showroom/products?category=${segment(code)}`
}
