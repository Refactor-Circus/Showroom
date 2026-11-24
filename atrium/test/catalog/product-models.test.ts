import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/product-models/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, lookups, mountShowroomPage, ok, oxford, resetShowroomPages, screensApi, showroomCalls } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { pick, row } from '../ui'

/*
 * `showroom.product-models` (AC 54, 71): every product model
 * (`product-models.index`), a cursor page at a time, with v1's search and
 * family variant filters sent as the endpoint's query. A new root model is
 * created with its code and family variant (`product-models.store`). A 422
 * shows the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/product-models'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    ...lookups(),
    'GET /product-models': ok(cursorPage([oxford], 'next-cursor')),
    ...overrides
  })
}

describe('showroom.product-models', () => {
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

  it('loads the product models and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/product-models')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.product-models', expect.anything())
    expect(row(wrapper, 'showroom-product-model-row-oxford').find('a').attributes('href')).toBe('/showroom/product-models/oxford')
    expect(row(wrapper, 'showroom-product-model-row-oxford').text()).toContain('shirts_by_size')
  })

  it('sends the search and family variant filters as the endpoint\'s query', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, `${PATH}?cursor=abc`)
    await wrapper.find('[data-testid="showroom-product-models-search"]').setValue('ox')
    await pick(wrapper, 'showroom-product-models-variant', 'shirts_by_size')
    await wrapper.find('[data-testid="showroom-product-models-filters"]').trigger('submit')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/product-models').at(-1)?.query).toEqual({ search: 'ox', family_variant: 'shirts_by_size' })
  })

  it('pages with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-models-next"]').trigger('click')
    await settle()
    await settle()

    expect(useRouter().currentRoute.value.query).toEqual({ cursor: 'next-cursor' })
    expect(showroomCalls('GET', '/product-models').at(-1)?.query).toEqual({ cursor: 'next-cursor' })
  })

  it('creates a root model with its code and family variant, then reloads', async () => {
    showroomWith({ 'POST /product-models': ok({ data: oxford }, 201) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-model-create-code"]').setValue(' oxford ')
    await pick(wrapper, 'showroom-product-model-create-variant', 'shirts_by_size')
    await wrapper.find('[data-testid="showroom-product-model-create-form"]').trigger('submit')
    await settle()

    const [call] = showroomCalls('POST', '/product-models')
    expect(call?.path).toBe('/product-models')
    expect(call?.body).toEqual({ code: 'oxford', family_variant: 'shirts_by_size' })
    expect(showroomCalls('GET', '/product-models')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on create', async () => {
    showroomWith({ 'POST /product-models': invalid({ code: ['The code has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-model-create-code"]').setValue('oxford')
    await pick(wrapper, 'showroom-product-model-create-variant', 'shirts_by_size')
    await wrapper.find('[data-testid="showroom-product-model-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.text()).toContain('The code has already been taken.')
  })

  it('shows the no-access notice when create answers 403', async () => {
    showroomWith({ 'POST /product-models': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-model-create-code"]').setValue('oxford')
    await wrapper.find('[data-testid="showroom-product-model-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /product-models': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-product-models-table"]').exists()).toBe(false)
  })
})
