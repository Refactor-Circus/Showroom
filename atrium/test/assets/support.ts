import type { components } from '../../../client/src/schema'
import type { components as ImpexComponents } from '../../types/impex-schema'
import type { ShowroomAsset } from '../../src/components/assets/assets'
import type { ShowroomOwner, ShowroomOwnerType } from '../../src/components/owners/owners'
import type { TransferFlow, TransferRun } from '../../src/components/transfers/transfers'
import type { AtriumIntegration } from '@refactor-circus/atrium'
import { settle } from '@refactor-circus/atrium/testing'
import { integrationEntry, mountIntegration } from '@refactor-circus/atrium/testing'
import { screenOf } from '../catalog/support'

/*
 * The payloads of the Showroom assets, owners and transfers suites (AC 54,
 * 71). The call filter, the confirmation stub and `ok()`/`invalid()` are the
 * catalog suites' (`../catalog/support`).
 *
 * Where Showroom's generated `client/src/schema.d.ts` declares a resource as Showroom's
 * `toArray()` builds it (assets, owner types, runs), each payload satisfies
 * both that schema and the shape the pages read. Where Scramble types it
 * loosely (an owner's `chain` as `unknown[]`, `children` as an object, its
 * `external_ids` as `Record<string, never>`), it satisfies the page's shape
 * only.
 */

type Schemas = components['schemas']

const STAMPS = { created_at: '2026-11-01T10:00:00+00:00', updated_at: '2026-11-02T10:00:00+00:00' }

export const hero = {
  id: '01JASSETHERO',
  code: 'hero',
  labels: { en: 'Hero shot', fr: 'Photo' },
  filename: 'hero.jpg',
  mime_type: 'image/jpeg',
  size: 20480,
  checksum: 'abc123',
  url: 'http://localhost/storage/hero.jpg',
  links: [
    { type: 'product', target: 'TEE-1', role: 'image', sort_order: 0 },
    { type: 'owner', target: 'acme', role: null, sort_order: 0 }
  ],
  ...STAMPS
} satisfies ShowroomAsset & Schemas['AssetResource']

export const manual = {
  id: '01JASSETMANUAL',
  code: 'manual',
  labels: {},
  filename: 'size-chart.pdf',
  mime_type: 'application/pdf',
  size: 1024,
  checksum: null,
  url: 'http://localhost/storage/size-chart.pdf',
  ...STAMPS
} satisfies ShowroomAsset & Schemas['AssetResource']

export const brand = {
  id: '01JTYPEBRAND',
  code: 'brand',
  labels: { en: 'Brand', fr: 'Marque' },
  parent_types: null,
  can_be_root: true,
  owns_products: true,
  sort_order: 0,
  owners_count: 2,
  ...STAMPS
} satisfies ShowroomOwnerType & Schemas['OwnerTypeResource']

export const store = {
  id: '01JTYPESTORE',
  code: 'store',
  labels: { en: 'Store' },
  parent_types: ['brand'],
  can_be_root: false,
  owns_products: false,
  sort_order: 1,
  owners_count: 0,
  ...STAMPS
} satisfies ShowroomOwnerType

/** `owners.index`: each with its type, parent and children counted. */
export const acme = {
  id: '01JOWNERACME',
  code: 'acme',
  slug: 'acme',
  type: 'brand',
  labels: { en: 'Acme', fr: 'Acmé' },
  parent: null,
  depth: 0,
  enabled: true,
  children_count: 1,
  external_ids: { erp: 'A-1' },
  external_ids_synced_at: { erp: null },
  flags: ['eco'],
  effective_flags: ['eco'],
  ...STAMPS
} satisfies ShowroomOwner

export const downtown = {
  ...acme,
  id: '01JOWNERDOWNTOWN',
  code: 'downtown',
  slug: 'downtown',
  type: 'store',
  labels: { en: 'Downtown' },
  parent: 'acme',
  depth: 1,
  children_count: 0,
  external_ids: {},
  external_ids_synced_at: {},
  flags: [],
  effective_flags: ['eco']
} satisfies ShowroomOwner

/** `owners.show` of `acme`: its chain, children, assets and counts. */
export const acmeShown = {
  ...acme,
  chain: ['acme'],
  children: ['downtown'],
  assets: [{ code: 'hero', role: 'logo', sort_order: 0, url: 'http://localhost/storage/hero.jpg', mime_type: 'image/jpeg', inherited: false }],
  products_count: 3,
  product_models_count: 1
} satisfies ShowroomOwner

/** `owners.show` of `downtown`, under `acme`. */
export const downtownShown = {
  ...downtown,
  chain: ['acme', 'downtown'],
  children: [],
  assets: [],
  products_count: 0,
  product_models_count: 0
} satisfies ShowroomOwner

export const eco = { id: '01JFLAGECO', code: 'eco', labels: { en: 'Eco' }, applies_to: ['product', 'owner'], sort_order: 0, ...STAMPS } satisfies Schemas['FlagResource']
export const premium = { id: '01JFLAGPREMIUM', code: 'premium', labels: { en: 'Premium' }, applies_to: ['owner'], sort_order: 1, ...STAMPS } satisfies Schemas['FlagResource']
export const sale = { id: '01JFLAGSALE', code: 'sale', labels: { en: 'Sale' }, applies_to: ['product'], sort_order: 2, ...STAMPS } satisfies Schemas['FlagResource']

type ImpexSchemas = ImpexComponents['schemas']

/** A flow `impex.flows.index` lists. */
export function flow(slug: string): TransferFlow {
  return { slug, class: `App\\Flows\\${slug.replace(/\W/g, '')}`, enabled: true, schedule: null }
}

/** A run `impex.runs.index` lists, of `flow`, created at `created`. */
export function run(id: string, flowSlug: string, created: string, overrides: Partial<TransferRun> = {}): TransferRun {
  return {
    id,
    flow: flowSlug,
    status: 'completed',
    trigger: 'api',
    version: null,
    idempotency_key: null,
    tags: null,
    error: null,
    parent_run_id: null,
    close_policy: null,
    expires_at: null,
    started_at: created,
    finished_at: created,
    created_at: created,
    updated_at: created,
    ...overrides
  } satisfies TransferRun & ImpexSchemas['RunResource']
}

/** The family, category tree and channel pick lists of the export form. */
export const shirts = { id: '01JFAMILYSHIRTS', code: 'shirts', labels: { en: 'Shirts' }, sort_order: 0, created_at: null, updated_at: null }
export const master = { id: '01JCATMASTER', code: 'master', labels: { en: 'Master' }, parent: null, sort_order: 0, depth: 0, created_at: null, updated_at: null }
export const webshop = { id: '01JCHANNELWEB', code: 'webshop', labels: { en: 'Webshop' }, locales: ['en'], currencies: [] }

/** The component key of the `row.actions` section the transfers suite places on each run. */
export const ROW_SECTION = 'test.transfer-row'

/** `atrium.screens.show` for the transfers screen, with its flow prefix (D-042) and, when asked, a section in `row.actions`. */
export function transfersScreen(prefix: string | null = 'showroom:', rowActions = false) {
  const screen = screenOf('showroom.transfers')

  return {
    data: {
      ...screen.data,
      options: prefix === null ? {} : { flow_prefix: prefix },
      sections: rowActions ? { 'row.actions': [{ key: ROW_SECTION, label: 'Run', component: ROW_SECTION, data: { slot: 'row.actions' } }] } : {}
    }
  }
}

/** A `useAtriumApi()` stand-in answering `/screens/{key}` with `screen` for the transfers screen. */
export function transfersApi(screen: ReturnType<typeof transfersScreen>) {
  return (path: string): Promise<unknown> => path === '/screens/showroom.transfers'
    ? Promise.resolve(screen)
    : Promise.reject(new Error(`Unexpected Atrium API call: ${path}`))
}

const mounted: { unmount: () => void }[] = []

/**
 * Mounts a page of this area at `route` with `integrations` in the shell (by
 * default Showroom and Impex, both active), and lets it load.
 */
export async function mountAreaPage<T>(page: T, route: string, integrations: AtriumIntegration[] = [integrationEntry('showroom'), integrationEntry('impex')]) {
  const wrapper = await mountIntegration(page, 'showroom', { route, integrations })
  mounted.push(wrapper)
  await settle()
  await settle()
  await settle()

  return wrapper
}

/** Unmounts every page `mountAreaPage()` mounted. Call it in `afterEach`, with `resetShowroomPages()`. */
export function resetAreaPages(): void {
  for (const wrapper of mounted.splice(0)) {
    wrapper.unmount()
  }
}

/** Gives `input` the `files` a user picked, then fires its change event. */
export async function pickFile(input: { element: Element, trigger: (event: string) => Promise<void> }, file: File): Promise<void> {
  Object.defineProperty(input.element, 'files', { value: [file], configurable: true })
  await input.trigger('change')
}
