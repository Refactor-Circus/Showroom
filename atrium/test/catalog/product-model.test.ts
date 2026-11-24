import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/product-models/[productModel].vue'
import type { ShowroomFamilyVariant, ShowroomProductModel } from '../../src/components/catalog/catalog'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { bySize, confirmWith, invalid, lookups, mountShowroomPage, ok, oxford, resetShowroomPages, screensApi, shirt, showroomCalls } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { pick, picked } from '../ui'

/*
 * `showroom.product-model` (AC 54, 71): one product model
 * (`product-models.show`) and its family variant. One save
 * (`product-models.update`) sends the owner (a root model only), the own
 * categories and the values its level sets. A model on the last level lists
 * its variant products and makes one (`products.store` with the model as
 * parent and the axis values); any other lists its sub-models and makes one
 * (`product-models.store`). Assets and associations work as on a product;
 * delete is confirmed. A 422 shows the field errors, a 403 no access and a
 * 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/product-models/oxford'

/** A two-level variant: models by fit, then products by size. */
const byFitAndSize = {
  ...bySize,
  id: '01JVARIANTFITSIZE',
  code: 'shirts_by_fit_size',
  levels: [
    { level: 1, axes: ['color'], attributes: [] },
    { level: 2, axes: ['size'], attributes: ['weight'] }
  ]
} satisfies ShowroomFamilyVariant

const tall = {
  ...oxford,
  id: '01JMODELTALL',
  code: 'oxford-tall',
  family_variant: 'shirts_by_fit_size',
  children: ['oxford-tall-blue'],
  products: []
} satisfies ShowroomProductModel

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    ...lookups(),
    'GET /product-models/{productModel}': call => ok({ data: call.path.endsWith('/oxford-tall') ? tall : oxford }),
    'GET /family-variants/{familyVariant}': call => ok({ data: call.path.endsWith('/shirts_by_fit_size') ? byFitAndSize : bySize }),
    'PATCH /product-models/{productModel}': ok({ data: oxford }),
    ...overrides
  })
}

async function submit(wrapper: Awaited<ReturnType<typeof mountShowroomPage>>): Promise<void> {
  await wrapper.find('[data-testid="showroom-product-model-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.product-model', () => {
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

  it('loads the model and its family variant, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/product-models/{productModel}')[0]?.path).toBe('/product-models/oxford')
    expect(showroomCalls('GET', '/family-variants/{familyVariant}')[0]?.path).toBe('/family-variants/shirts_by_size')
    expect(api).toHaveBeenCalledWith('/screens/showroom.product-model', expect.objectContaining({ query: expect.objectContaining({ record: 'oxford' }) }))
    expect(wrapper.find('[data-testid="showroom-product-model-title"]').text()).toBe('oxford')
    expect((wrapper.find('[data-testid="showroom-value-name"]').element as HTMLInputElement).value).toBe('Oxford')
    expect(wrapper.find('[data-testid="showroom-variant-product-oxford-s"]').attributes('href')).toBe('/showroom/products?search=oxford-s')
  })

  it('saves the owner, own categories and the level\'s values, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-model-owner"]').setValue('globex')
    await wrapper.find('[data-testid="showroom-product-model-categories"]').setValue('tops, men')
    await wrapper.find('[data-testid="showroom-value-name"]').setValue('Oxford classic')
    await submit(wrapper)

    const [call] = showroomCalls('PATCH', '/product-models/{productModel}')
    expect(call?.path).toBe('/product-models/oxford')
    expect(call?.body).toEqual({
      owner: 'globex',
      categories: ['tops', 'men'],
      values: { name: [{ locale: 'en', scope: null, data: 'Oxford classic' }] }
    })
    expect(showroomCalls('GET', '/product-models/{productModel}')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /product-models/{productModel}': invalid({ 'values.name': ['The name is required.'], 'owner': ['The owner is unknown.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await submit(wrapper)

    expect(wrapper.find('[data-testid="showroom-value-error-name"]').text()).toBe('The name is required.')
    expect(wrapper.text()).toContain('The owner is unknown.')
  })

  it('makes a variant product with the model as parent and its axis values, then reloads', async () => {
    showroomWith({ 'POST /products': ok({ data: shirt }, 201) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-child-key"]').setValue('oxford-m')
    await pick(wrapper, 'showroom-child-axis-size', 'm')
    await wrapper.find('[data-testid="showroom-child-create-form"]').trigger('submit')
    await settle()
    await settle()

    const [call] = showroomCalls('POST', '/products')
    expect(call?.body).toEqual({ identifier: 'oxford-m', parent: 'oxford', values: { size: [{ locale: null, scope: null, data: 'm' }] } })
    expect(showroomCalls('GET', '/product-models/{productModel}')).toHaveLength(2)
  })

  it('makes a sub-model on a model above the last level', async () => {
    showroomWith({ 'POST /product-models': ok({ data: tall }, 201) })

    const wrapper = await mountShowroomPage(Page, '/showroom/product-models/oxford-tall')

    expect(wrapper.find('[data-testid="showroom-sub-model-oxford-tall-blue"]').attributes('href')).toBe('/showroom/product-models/oxford-tall-blue')
    await wrapper.find('[data-testid="showroom-child-key"]').setValue('oxford-tall-red')
    await pick(wrapper, 'showroom-child-axis-color', 'red')
    await wrapper.find('[data-testid="showroom-child-create-form"]').trigger('submit')
    await settle()

    expect(showroomCalls('POST', '/product-models')[0]?.body).toEqual({ code: 'oxford-tall-red', parent: 'oxford-tall', values: { color: [{ locale: null, scope: null, data: 'red' }] } })
  })

  it('shows the field errors of a 422 on a new variant', async () => {
    showroomWith({ 'POST /products': invalid({ 'values.size': ['This size is taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-child-key"]').setValue('oxford-s2')
    await pick(wrapper, 'showroom-child-axis-size', 's')
    await wrapper.find('[data-testid="showroom-child-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-axis="size"]').text()).toContain('This size is taken.')
  })

  it('links an uploaded asset to the model', async () => {
    showroomWith({
      'POST /assets': ok({ data: { code: 'photo-9' } }, 201),
      'POST /assets/{asset}/links': ok({ data: {} }, 201)
    })

    const wrapper = await mountShowroomPage(Page, PATH)
    const input = wrapper.find('[data-testid="showroom-asset-file"]')
    Object.defineProperty(input.element, 'files', { value: [new File(['jpeg'], 'model.jpg', { type: 'image/jpeg' })], configurable: true })
    await input.trigger('change')
    await wrapper.find('[data-testid="showroom-asset-upload"]').trigger('submit')
    await settle()

    expect(showroomCalls('POST', '/assets/{asset}/links')[0]?.body).toEqual({ type: 'product_model', target: 'oxford', role: 'image' })
  })

  it('adds an association through the model\'s update', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await pick(wrapper, 'showroom-association-type', 'related')
    await wrapper.find('[data-testid="showroom-association-target"]').setValue('shirt-1')
    await wrapper.find('[data-testid="showroom-association-add"]').trigger('submit')
    await settle()

    expect(showroomCalls('PATCH', '/product-models/{productModel}')[0]?.body).toEqual({ associations: { related: { products: ['shirt-1'], product_models: [] } } })
  })

  it('deletes the model once confirmed, then returns to the list', async () => {
    showroomWith({ 'DELETE /product-models/{productModel}': reply(204) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-model-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/product-models/{productModel}')[0]?.path).toBe('/product-models/oxford')
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/product-models'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith({ 'DELETE /product-models/{productModel}': reply(204) })
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-product-model-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/product-models/{productModel}')).toHaveLength(0)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'PATCH /product-models/{productModel}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await submit(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /product-models/{productModel}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-product-model-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /product-models/{productModel}': reply(404) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
