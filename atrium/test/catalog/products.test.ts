import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/products/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { blouse, lookups, mountShowroomPage, numberedPage, ok, resetShowroomPages, screensApi, shirt, showroomCalls } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { pick, row } from '../ui'

/*
 * `showroom.products` (AC 54, 71): the product list (`products.index`), paged
 * by number as product search pages. Its filters (search, family, category,
 * owner, workflow status, enabled) live in the URL and go to the endpoint as
 * its query. A 403 shows no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    ...lookups(),
    'GET /products': ok(numberedPage([shirt, blouse], 1, 3)),
    ...overrides
  })
}

describe('showroom.products', () => {
  beforeEach(() => {
    clearAtriumData()
    api.mockReset()
    api.mockImplementation(screensApi)
  })

  afterEach(async () => {
    resetShowroomPages()
    resetPackageApis()
    vi.restoreAllMocks()
    await useRouter().replace('/')
  })

  it('loads the first page of products and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, '/showroom/products')

    expect(showroomCalls('GET', '/products')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.products', expect.anything())
    expect(row(wrapper, `showroom-product-row-${shirt.id}`).text()).toContain('shirt-1')
    expect(row(wrapper, `showroom-product-row-${shirt.id}`).text()).toContain('Oxford shirt')
    expect(row(wrapper, `showroom-product-row-${shirt.id}`).text()).toContain('Shirts')
    expect(row(wrapper, `showroom-product-row-${shirt.id}`).find('a').attributes('href')).toBe(`/showroom/products/${shirt.id}`)
    expect(row(wrapper, `showroom-product-row-${blouse.id}`).find('[data-status="approved"]').exists()).toBe(true)
    expect(row(wrapper, `showroom-product-row-${blouse.id}`).find('[data-status="disabled"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-products-page"]').text()).toContain('1')
  })

  it('sends the status filter as the endpoint\'s query and starts again from the first page', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, '/showroom/products?page=2')
    await pick(wrapper, 'showroom-products-status', 'in_review')
    await wrapper.find('[data-testid="showroom-products-search"]').setValue('oxford')
    await wrapper.find('[data-testid="showroom-products-filters"]').trigger('submit')
    await settle()
    await settle()

    expect(useRouter().currentRoute.value.query).toEqual({ status: 'in_review', search: 'oxford' })
    expect(showroomCalls('GET', '/products').at(-1)?.query).toEqual({ status: 'in_review', search: 'oxford' })
  })

  it('reads the filters and page from the URL', async () => {
    showroomWith()

    await mountShowroomPage(Page, '/showroom/products?status=approved&family=shirts&enabled=0&page=2')

    expect(showroomCalls('GET', '/products')[0]?.query).toEqual({ status: 'approved', family: 'shirts', enabled: '0', page: '2' })
  })

  it('pages forward by number', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, '/showroom/products?status=draft')
    await wrapper.find('[data-testid="showroom-products-next"]').trigger('click')
    await settle()
    await settle()

    expect(useRouter().currentRoute.value.query).toEqual({ status: 'draft', page: '2' })
    expect(showroomCalls('GET', '/products').at(-1)?.query).toEqual({ status: 'draft', page: '2' })
  })

  it('clears the filters', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, '/showroom/products?status=draft&search=x')
    await wrapper.find('[data-testid="showroom-products-clear"]').trigger('click')
    await settle()
    await settle()

    expect(useRouter().currentRoute.value.query).toEqual({})
    expect(showroomCalls('GET', '/products').at(-1)?.query).toEqual({})
  })

  it('shows the empty state', async () => {
    showroomWith({ 'GET /products': ok(numberedPage([])) })

    const wrapper = await mountShowroomPage(Page, '/showroom/products')

    expect(wrapper.find('[data-testid="showroom-products-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-products-next"]').exists()).toBe(false)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /products': reply(403) })

    const wrapper = await mountShowroomPage(Page, '/showroom/products')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-products-table"]').exists()).toBe(false)
  })
})
