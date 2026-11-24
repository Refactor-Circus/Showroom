import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/products/create.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { invalid, lookups, mountShowroomPage, ok, resetShowroomPages, screensApi, shirt, showroomCalls } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.product-create` (AC 54, 71): a new product (`products.store`) with
 * v1's fields, its identifier and optional family, then the new product's
 * page. A 422 shows the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/products/create'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', { ...lookups(), ...overrides })
}

describe('showroom.product-create', () => {
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

  it('offers the families and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(api).toHaveBeenCalledWith('/screens/showroom.product-create', expect.anything())
    expect(showroomCalls('GET', '/families')[0]?.query).toEqual({ per_page: '100' })
    expect(wrapper.find('[data-testid="showroom-product-create-family"]').text()).toContain('Shirts')
  })

  it('creates the product with its identifier and family, then opens it', async () => {
    showroomWith({ 'POST /products': ok({ data: shirt }, 201) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-create-identifier"]').setValue(' shirt-1 ')
    await wrapper.find('[data-testid="showroom-product-create-family"]').setValue('shirts')
    await wrapper.find('[data-testid="showroom-product-create-form"]').trigger('submit')
    await settle()

    const [call] = showroomCalls('POST', '/products')
    expect(call?.path).toBe('/products')
    expect(call?.body).toEqual({ identifier: 'shirt-1', family: 'shirts' })
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe(`/showroom/products/${shirt.id}`))
  })

  it('leaves the family out when none is picked', async () => {
    showroomWith({ 'POST /products': ok({ data: shirt }, 201) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-create-identifier"]').setValue('loose-1')
    await wrapper.find('[data-testid="showroom-product-create-form"]').trigger('submit')
    await settle()

    expect(showroomCalls('POST', '/products')[0]?.body).toEqual({ identifier: 'loose-1' })
  })

  it('shows the field errors of a 422 and stays on the form', async () => {
    showroomWith({ 'POST /products': invalid({ identifier: ['The identifier has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-create-identifier"]').setValue('shirt-1')
    await wrapper.find('[data-testid="showroom-product-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.text()).toContain('The identifier has already been taken.')
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })

  it('shows the no-access notice on a 403', async () => {
    showroomWith({ 'POST /products': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-create-identifier"]').setValue('shirt-1')
    await wrapper.find('[data-testid="showroom-product-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })
})
