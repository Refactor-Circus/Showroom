import { vi } from 'vitest'
import type {
  CatalogList,
  ShowroomAssociationType,
  ShowroomAttribute,
  ShowroomAttributeOption,
  ShowroomChannel,
  ShowroomFamily,
  ShowroomFamilyVariant,
  ShowroomFlag,
  ShowroomLocale,
  ShowroomProduct,
  ShowroomProductModel,
  ShowroomVersion
} from '../../src/components/catalog/catalog'
import type { AtriumIntegrationScreenData } from '@refactor-circus/atrium'
import { settle } from '@refactor-circus/atrium/testing'
import { mountIntegration, packageCalls, reply, type PackageCall, type PackageReply, type PackageRoutes } from '@refactor-circus/atrium/testing'

/*
 * Shared pieces of the Showroom catalog suites (AC 54, 71): products, product
 * models, families and family variants.
 *
 * Scramble types most of Showroom's resources loosely in the generated
 * `client/src/schema.d.ts` (a product list as `string[]`, `values` as a string),
 * so the payloads are typed with `satisfies` against the shapes the pages read
 * (`components/catalog/catalog.ts`), and answered with `ok()` rather than as
 * the schema's documented body. The `atrium.screens.show` answer, a page
 * mounter and the call filters live here too.
 */

export const nameAttribute = {
  id: '01JATTRNAME',
  code: 'name',
  type: 'text',
  group: null,
  labels: { en: 'Name' },
  is_unique: false,
  is_localizable: true,
  is_scopable: false,
  settings: {},
  sort_order: 0
} satisfies ShowroomAttribute

export const colorAttribute = {
  id: '01JATTRCOLOR',
  code: 'color',
  type: 'select',
  group: null,
  labels: { en: 'Color' },
  is_unique: false,
  is_localizable: false,
  is_scopable: false,
  settings: {},
  sort_order: 1
} satisfies ShowroomAttribute

export const sizeAttribute = {
  ...colorAttribute,
  id: '01JATTRSIZE',
  code: 'size',
  labels: { en: 'Size' },
  sort_order: 2
} satisfies ShowroomAttribute

export const weightAttribute = {
  ...nameAttribute,
  id: '01JATTRWEIGHT',
  code: 'weight',
  type: 'number',
  labels: { en: 'Weight' },
  is_localizable: false,
  sort_order: 3
} satisfies ShowroomAttribute

export const ATTRIBUTES = [nameAttribute, colorAttribute, sizeAttribute, weightAttribute]

export const OPTIONS: Record<string, ShowroomAttributeOption[]> = {
  color: [
    { id: '01JOPTRED', code: 'red', labels: { en: 'Red' }, sort_order: 0 },
    { id: '01JOPTBLUE', code: 'blue', labels: { en: 'Blue' }, sort_order: 1 }
  ],
  size: [
    { id: '01JOPTS', code: 's', labels: { en: 'Small' }, sort_order: 0 },
    { id: '01JOPTM', code: 'm', labels: { en: 'Medium' }, sort_order: 1 }
  ]
}

export const english = { id: '01JLOCALEEN', code: 'en', labels: { en: 'English' } } satisfies ShowroomLocale
export const french = { id: '01JLOCALEFR', code: 'fr', labels: { en: 'French' } } satisfies ShowroomLocale

export const web = { id: '01JCHANNELWEB', code: 'web', labels: { en: 'Web' }, locales: ['en', 'fr'], currencies: ['USD'] } satisfies ShowroomChannel

export const eco = { id: '01JFLAGECO', code: 'eco', labels: { en: 'Eco' }, applies_to: ['product'], sort_order: 0 } satisfies ShowroomFlag
export const sale = { id: '01JFLAGSALE', code: 'sale', labels: { en: 'Sale' }, applies_to: ['product', 'category'], sort_order: 1 } satisfies ShowroomFlag

export const related = { id: '01JASSOCRELATED', code: 'related', labels: { en: 'Related' }, is_two_way: false, is_quantified: false } satisfies ShowroomAssociationType
export const bundle = { id: '01JASSOCBUNDLE', code: 'bundle', labels: { en: 'Bundle' }, is_two_way: false, is_quantified: true } satisfies ShowroomAssociationType

export const shirts = {
  id: '01JFAMILYSHIRTS',
  code: 'shirts',
  labels: { en: 'Shirts' },
  label_attribute: 'name',
  sort_order: 1,
  attributes_count: 2,
  attributes: [
    { attribute: 'name', type: 'text', group: null, is_required: true, required_channels: null, sort_order: 0 },
    { attribute: 'color', type: 'select', group: 'looks', is_required: false, required_channels: ['web'], sort_order: 1 }
  ],
  created_at: '2026-11-01T10:00:00+00:00',
  updated_at: '2026-11-01T10:00:00+00:00'
} satisfies ShowroomFamily

export const shoes = {
  ...shirts,
  id: '01JFAMILYSHOES',
  code: 'shoes',
  labels: { en: 'Shoes' },
  label_attribute: null,
  attributes_count: 0,
  attributes: []
} satisfies ShowroomFamily

export const bySize = {
  id: '01JVARIANTSIZE',
  code: 'shirts_by_size',
  family: 'shirts',
  labels: { en: 'Shirts by size' },
  levels: [{ level: 1, axes: ['size'], attributes: ['color'] }],
  common_attributes: ['name'],
  product_models_count: 1,
  created_at: '2026-11-01T10:00:00+00:00',
  updated_at: '2026-11-01T10:00:00+00:00'
} satisfies ShowroomFamilyVariant

export const shirt = {
  id: '01JPRODUCTSHIRT',
  identifier: 'shirt-1',
  slug: 'shirt-1',
  family: 'shirts',
  parent: null,
  owner: 'acme',
  categories: ['tops'],
  assets: [{ code: 'photo-1', role: 'image', sort_order: 0, url: 'http://localhost/photo-1.jpg', mime_type: 'image/jpeg', inherited: false }],
  enabled: true,
  effective_enabled: true,
  status: 'draft',
  published_version: null,
  published_at: null,
  completeness: [{ scope: 'web', locale: 'en', required: 2, missing: 1, ratio: 50, missing_attributes: ['color'] }],
  values: {
    name: [{ locale: 'en', scope: null, data: 'Oxford shirt' }],
    color: [{ locale: null, scope: null, data: 'red' }]
  },
  associations: { related: { products: ['shirt-2'], product_models: [] } },
  quantified_associations: {},
  external_ids: { erp: 'E-1' },
  external_ids_synced_at: { erp: '2026-11-20T10:00:00+00:00' },
  flags: ['eco'],
  effective_flags: ['eco', 'sale'],
  created_at: '2026-11-01T10:00:00+00:00',
  updated_at: '2026-11-02T10:00:00+00:00',
  changed_at: '2026-11-02T10:00:00+00:00'
} satisfies ShowroomProduct

export const blouse = {
  ...shirt,
  id: '01JPRODUCTBLOUSE',
  identifier: 'blouse-1',
  slug: 'blouse-1',
  family: 'shoes',
  enabled: false,
  status: 'approved',
  published_version: 3,
  published_at: '2026-11-03T10:00:00+00:00',
  values: {}
} satisfies ShowroomProduct

export const oxford = {
  id: '01JMODELOXFORD',
  code: 'oxford',
  family_variant: 'shirts_by_size',
  parent: null,
  owner: 'acme',
  categories: ['tops'],
  assets: [],
  level: 0,
  values: { name: [{ locale: 'en', scope: null, data: 'Oxford' }] },
  children: [],
  products: ['oxford-s'],
  associations: {},
  quantified_associations: {},
  created_at: '2026-11-01T10:00:00+00:00',
  updated_at: '2026-11-02T10:00:00+00:00'
} satisfies ShowroomProductModel

/** A variant product of `oxford`: it shows the model's values merged into its own. */
export const oxfordSmall = {
  ...shirt,
  id: '01JPRODUCTOXFORDS',
  identifier: 'oxford-s',
  slug: 'oxford-s',
  family: 'shirts',
  parent: 'oxford',
  categories: ['tops', 'sale'],
  values: {
    name: [{ locale: 'en', scope: null, data: 'Oxford' }],
    size: [{ locale: null, scope: null, data: 's' }],
    color: [{ locale: null, scope: null, data: 'blue' }]
  },
  associations: {},
  flags: [],
  effective_flags: []
} satisfies ShowroomProduct

export const versionTwo = {
  version: 2,
  action: 'updated',
  comment: 'Fixed the name',
  author: { type: 'user', id: 7 },
  changes: { name: ['Shirt', 'Oxford shirt'] },
  created_at: '2026-11-02T10:00:00+00:00'
} satisfies ShowroomVersion

export const versionOne = {
  ...versionTwo,
  version: 1,
  action: 'created',
  comment: null,
  changes: {},
  created_at: '2026-11-01T10:00:00+00:00'
} satisfies ShowroomVersion

/** A cursor page, as Showroom's lists answer. */
export function cursorPage<T>(data: T[], next: string | null = null, prev: string | null = null): CatalogList<T> {
  return { data, meta: { next_cursor: next, prev_cursor: prev } }
}

/** A numbered page, as product search answers. */
export function numberedPage<T>(data: T[], current = 1, last = 1): CatalogList<T> {
  return { data, meta: { current_page: current, last_page: last } }
}

/**
 * A success answer with `body`. Showroom's generated schema types most bodies
 * loosely, so the mocks answer the shapes the pages read through `reply()`.
 */
export function ok(body: unknown, status = 200): PackageReply {
  return reply(status, body)
}

/**
 * The lookups every catalog page may make: attributes and their options,
 * locales, channels, families, family variants, flags and association types.
 */
export function lookups(): PackageRoutes<'showroom'> {
  return {
    'GET /attributes': ok(cursorPage(ATTRIBUTES)),
    'GET /attributes/{attribute}/options': call => ok(cursorPage(OPTIONS[call.path.split('/')[2] ?? ''] ?? [])),
    'GET /locales': ok(cursorPage([english, french])),
    'GET /channels': ok(cursorPage([web])),
    'GET /families': ok(cursorPage([shirts, shoes])),
    'GET /family-variants': ok(cursorPage([bySize])),
    'GET /flags': ok(cursorPage([eco, sale])),
    'GET /association-types': ok(cursorPage([related, bundle]))
  }
}

/** The `atrium.screens.show` answer for a Showroom screen with no extensions. */
export function screenOf(key: string, record: string | null = null): { data: AtriumIntegrationScreenData } {
  return {
    data: {
      key,
      kind: record === null ? 'list' : 'detail',
      path: '/showroom',
      subject_type: null,
      record,
      open_tab: 'overview',
      tabs: [],
      sections: {},
      rows: {}
    }
  }
}

/** A `useAtriumApi()` stand-in answering `/screens/{key}` with `screenOf()`. */
export function screensApi(path: string, options?: { query?: Record<string, unknown> }): Promise<unknown> {
  const match = /^\/screens\/(.+)$/.exec(path)
  const record = options?.query?.record

  return match
    ? Promise.resolve(screenOf(decodeURIComponent(match[1]!), typeof record === 'string' ? record : null))
    : Promise.reject(new Error(`Unexpected Atrium API call: ${path}`))
}

const mounted: { unmount: () => void }[] = []

/** Mounts a Showroom page at `route`, with Showroom active in the shell, and lets it load. */
export async function mountShowroomPage<T>(page: T, route: string) {
  const wrapper = await mountIntegration(page, 'showroom', { route })
  mounted.push(wrapper)
  await settle()
  await settle()

  return wrapper
}

/**
 * Unmounts every page `mountShowroomPage()` mounted and drops the stubbed
 * globals. Call it in `afterEach`: a page left mounted keeps watching the
 * route and reloads against the next test's mocks.
 */
export function resetShowroomPages(): void {
  for (const wrapper of mounted.splice(0)) {
    wrapper.unmount()
  }

  vi.unstubAllGlobals()
}

/** The Showroom calls with `method` to the route template `route` (`/products/{product}`). */
export function showroomCalls(method: string, route: string): PackageCall[] {
  return packageCalls.filter(call => call.pkg === 'showroom' && call.method === method && call.route === route)
}

/**
 * Answers every `window.confirm()` with `answer` (happy-dom has no
 * `confirm` to spy on, so it is stubbed; `resetShowroomPages()` drops it).
 */
export function confirmWith(answer: boolean) {
  const confirm = vi.fn<(message?: string) => boolean>().mockReturnValue(answer)
  vi.stubGlobal('confirm', confirm)

  return confirm
}

/** A Laravel validation answer with `errors`. */
export function invalid(errors: Record<string, string[]>): PackageReply {
  return reply(422, { message: 'The given data was invalid.', errors })
}
