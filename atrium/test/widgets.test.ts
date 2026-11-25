import { afterEach, describe, expect, it } from 'vitest'
import type { VueWrapper } from '@vue/test-utils'
import CompletenessWidget from '../src/components/widgets/CompletenessWidget.vue'
import ProductStatusWidget from '../src/components/widgets/ProductStatusWidget.vue'
import RecentChangesWidget from '../src/components/widgets/RecentChangesWidget.vue'
import ReviewQueueWidget from '../src/components/widgets/ReviewQueueWidget.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, mountIntegration, reply, resetPackageApis, type PackageBody } from '@refactor-circus/atrium/testing'
import fixture from '@refactor-circus/atrium/fixtures/integrations/showroom.json'

/*
 * Showroom's four widgets (AC 60): `showroom.product-status`,
 * `showroom.completeness`, `showroom.review-queue` and
 * `showroom.recent-changes` each read their part of `showroom.stats.show`,
 * and all four on one dashboard share a single request. The PHP side sends
 * `data: {}`; a 403 renders the no-access card, any other failure the error
 * card.
 */

const stats = {
  data: {
    status: {
      counts: { draft: 4, in_review: 2, approved: 9, archived: 1 },
      published: 7
    },
    completeness: [
      { channel: 'web', locale: 'en_US', products: 12, complete: 8, average: 81 },
      { channel: 'web', locale: 'fr_FR', products: 12, complete: 3, average: 45 }
    ],
    review_queue: {
      products: [
        { id: '01JPRODUCT00000000000000A1', identifier: 'SKU-001', slug: 'sku-001', status: 'in_review', updated_at: '2026-11-20T09:00:00Z' },
        { id: '01JPRODUCT00000000000000A2', identifier: 'SKU-002', slug: 'sku-002', status: 'in_review', updated_at: '2026-11-21T09:00:00Z' }
      ],
      total: 2
    },
    recent_versions: [
      {
        version: 3,
        action: 'update',
        comment: null,
        author: { type: 'user', id: 5 },
        changes: {},
        created_at: '2026-11-22T10:00:00Z',
        subject: { type: 'product', id: '01JPRODUCT00000000000000A1', key: 'SKU-001' }
      },
      {
        version: 2,
        action: 'create',
        comment: null,
        author: null,
        changes: {},
        created_at: '2026-11-21T10:00:00Z',
        subject: { type: 'product_model', id: '01JMODEL0000000000000000B1', key: 'TSHIRT' }
      },
      {
        version: 1,
        action: 'create',
        comment: null,
        author: null,
        changes: {},
        created_at: '2026-11-20T10:00:00Z',
        subject: { type: 'product', id: '01JPRODUCT00000000000000Z9', key: null }
      }
    ]
  }
} satisfies PackageBody<'showroom', 'GET /stats'>

const emptyStats = {
  data: {
    ...stats.data,
    completeness: [],
    review_queue: { products: [], total: 0 },
    recent_versions: []
  }
} satisfies PackageBody<'showroom', 'GET /stats'>

type Widget = typeof ProductStatusWidget | typeof CompletenessWidget | typeof ReviewQueueWidget | typeof RecentChangesWidget

const mounted: VueWrapper[] = []

async function mount(component: Widget, key: string) {
  const widget = fixture.widgets.find(candidate => candidate.key === key)!
  const wrapper = await mountIntegration(component, 'showroom', { props: { data: widget.data, settings: {} } })
  mounted.push(wrapper as unknown as VueWrapper)
  await settle()

  return wrapper
}

afterEach(() => {
  // Unmounted, so the next test's widgets ask again rather than share.
  for (const wrapper of mounted.splice(0)) {
    try {
      wrapper.unmount()
    } catch {
      // Already unmounted.
    }
  }

  resetPackageApis()
})

describe('the four showroom widgets on one dashboard', () => {
  it('are declared with static data only', () => {
    expect(fixture.widgets.map(widget => [widget.key, widget.data])).toEqual([
      ['showroom.product-status', {}],
      ['showroom.completeness', {}],
      ['showroom.review-queue', {}],
      ['showroom.recent-changes', {}]
    ])
  })

  it('share one showroom.stats.show request while they are mounted', async () => {
    const calls = mockPackageApi('showroom', { 'GET /stats': stats })

    await mount(ProductStatusWidget, 'showroom.product-status')
    await mount(CompletenessWidget, 'showroom.completeness')
    await mount(ReviewQueueWidget, 'showroom.review-queue')
    await mount(RecentChangesWidget, 'showroom.recent-changes')

    expect(calls.map(call => `${call.method} ${call.route}`)).toEqual(['GET /stats'])
  })

  it('each render the no-access card on a 403', async () => {
    mockPackageApi('showroom', { 'GET /stats': reply(403) })

    for (const [component, key] of [
      [ProductStatusWidget, 'showroom.product-status'],
      [CompletenessWidget, 'showroom.completeness'],
      [ReviewQueueWidget, 'showroom.review-queue'],
      [RecentChangesWidget, 'showroom.recent-changes']
    ] as const) {
      const wrapper = await mount(component, key)

      expect(wrapper.find('[data-testid="atrium-no-access"]').exists(), key).toBe(true)
      expect(wrapper.find('[data-testid="atrium-error-card"]').exists(), key).toBe(false)
    }
  })

  it('each render the error card on any other failure', async () => {
    mockPackageApi('showroom', { 'GET /stats': reply(500) })

    for (const [component, key] of [
      [ProductStatusWidget, 'showroom.product-status'],
      [CompletenessWidget, 'showroom.completeness'],
      [ReviewQueueWidget, 'showroom.review-queue'],
      [RecentChangesWidget, 'showroom.recent-changes']
    ] as const) {
      const wrapper = await mount(component, key)

      expect(wrapper.find('[data-testid="atrium-error-card"]').exists(), key).toBe(true)
      expect(wrapper.find('[data-testid="atrium-no-access"]').exists(), key).toBe(false)
    }
  })
})

describe('showroom.product-status', () => {
  it('renders each status count and the live count, linking to the filtered product list', async () => {
    mockPackageApi('showroom', { 'GET /stats': stats })

    const wrapper = await mount(ProductStatusWidget, 'showroom.product-status')
    const counts = wrapper.findAll('[data-testid="showroom-product-status-count"]')

    expect(Object.fromEntries(counts.map(count => [count.attributes('data-status'), count.text()]))).toEqual({
      draft: 'Draft4',
      in_review: 'In review2',
      approved: 'Approved9',
      archived: 'Archived1',
      published: 'Live7'
    })

    expect(counts.map(count => count.attributes('href'))).toEqual([
      '/showroom/products?status=draft',
      '/showroom/products?status=in_review',
      '/showroom/products?status=approved',
      '/showroom/products?status=archived',
      '/showroom/products?published=1'
    ])
  })
})

describe('showroom.completeness', () => {
  it('renders each channel and locale with its average and complete count', async () => {
    mockPackageApi('showroom', { 'GET /stats': stats })

    const wrapper = await mount(CompletenessWidget, 'showroom.completeness')
    const rows = wrapper.findAll('[data-testid="showroom-completeness-row"]')

    expect(rows.map(row => row.attributes('data-slot'))).toEqual(['web-en_US', 'web-fr_FR'])
    expect(rows[0]!.find('[data-testid="showroom-completeness-average"]').text()).toBe('81%')
    expect(rows[0]!.text()).toContain('8 of 12 complete')
    expect(decodeURIComponent(rows[0]!.find('a').attributes('href') ?? '')).toBe('/showroom/products?complete[scope]=web&complete[locale]=en_US&complete[min]=100')
  })

  it('says so when no scores exist yet', async () => {
    mockPackageApi('showroom', { 'GET /stats': emptyStats })

    const wrapper = await mount(CompletenessWidget, 'showroom.completeness')

    expect(wrapper.find('[data-testid="showroom-completeness-empty"]').exists()).toBe(true)
  })
})

describe('showroom.review-queue', () => {
  it('lists the products in review, each linking to its page, and the total linking to the list', async () => {
    mockPackageApi('showroom', { 'GET /stats': stats })

    const wrapper = await mount(ReviewQueueWidget, 'showroom.review-queue')
    const products = wrapper.findAll('[data-testid="showroom-review-queue-product"]')

    expect(products.map(product => product.attributes('data-product'))).toEqual(['SKU-001', 'SKU-002'])
    expect(products[0]!.find('a').attributes('href')).toBe('/showroom/products/01JPRODUCT00000000000000A1')
    expect(wrapper.find('[data-testid="showroom-review-queue-total"]').text()).toBe('2 waiting')
    expect(wrapper.find('[data-testid="showroom-review-queue-total"]').attributes('href')).toBe('/showroom/products?status=in_review')
  })

  it('says so when nothing waits', async () => {
    mockPackageApi('showroom', { 'GET /stats': emptyStats })

    const wrapper = await mount(ReviewQueueWidget, 'showroom.review-queue')

    expect(wrapper.find('[data-testid="showroom-review-queue-empty"]').exists()).toBe(true)
  })
})

describe('showroom.recent-changes', () => {
  it('lists the newest versions, linking products by id and product models by code', async () => {
    mockPackageApi('showroom', { 'GET /stats': stats })

    const wrapper = await mount(RecentChangesWidget, 'showroom.recent-changes')
    const changes = wrapper.findAll('[data-testid="showroom-recent-change"]')

    expect(changes.map(change => change.attributes('data-version'))).toEqual(['3', '2', '1'])
    expect(changes[0]!.find('a').attributes('href')).toBe('/showroom/products/01JPRODUCT00000000000000A1')
    expect(changes[0]!.text()).toContain('by 5')
    expect(changes[1]!.find('a').attributes('href')).toBe('/showroom/product-models/TSHIRT')
    expect(changes[2]!.find('a').exists()).toBe(false)
  })

  it('says so when there are none', async () => {
    mockPackageApi('showroom', { 'GET /stats': emptyStats })

    const wrapper = await mount(RecentChangesWidget, 'showroom.recent-changes')

    expect(wrapper.find('[data-testid="showroom-recent-changes-empty"]').exists()).toBe(true)
  })
})
