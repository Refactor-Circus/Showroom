import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/products/[product].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { pick, picked, row } from '../ui'
import {
  bySize,
  confirmWith,
  cursorPage,
  invalid,
  lookups,
  mountShowroomPage,
  ok,
  oxford,
  oxfordSmall,
  resetShowroomPages,
  screensApi,
  shirt,
  shirts,
  showroomCalls,
  versionOne,
  versionTwo
} from './support'

/*
 * `showroom.product` (AC 54, 71): one product (`products.show`), with its
 * family (`families.show`) or, for a variant, its product model and family
 * variant. One save (`products.update`) sends the family, owner, enabled,
 * categories, the values of the picked locale and channel, the external ids
 * and the own flags. Associations add and remove through the same update;
 * assets upload (`assets.store`) and link (`assets.links.store`). The
 * workflow takes transitions (`products.transitions.store`), the history
 * (`products.versions.index`) reverts (`products.revert`, confirmed), and
 * delete (`products.destroy`) is confirmed. A 422 shows the field errors, a
 * 403 no access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = `/showroom/products/${shirt.id}`

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    ...lookups(),
    'GET /products/{product}': call => ok({ data: [shirt, oxfordSmall].find(product => call.path === `/products/${product.id}`) ?? shirt }),
    'GET /products/{product}/versions': ok(cursorPage([versionTwo, versionOne])),
    'GET /families/{family}': ok({ data: shirts }),
    'GET /product-models/{productModel}': ok({ data: oxford }),
    'GET /family-variants/{familyVariant}': ok({ data: bySize }),
    'PATCH /products/{product}': ok({ data: shirt }),
    ...overrides
  })
}

async function submit(wrapper: Awaited<ReturnType<typeof mountShowroomPage>>): Promise<void> {
  await wrapper.find('[data-testid="showroom-product-form"]').trigger('submit')
  await settle()
  await settle()
}

/** What a save sends for `shirt` untouched. */
const UNCHANGED = {
  family: 'shirts',
  owner: 'acme',
  enabled: true,
  categories: ['tops'],
  values: {
    name: [{ locale: 'en', scope: null, data: 'Oxford shirt' }],
    color: [{ locale: null, scope: null, data: 'red' }]
  },
  external_ids: { erp: 'E-1' },
  flags: ['eco']
}

describe('showroom.product', () => {
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

  describe('loading', () => {
    it('loads the product, its history and its family, and calls atrium.screens.show with its key and record', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(showroomCalls('GET', '/products/{product}')[0]?.path).toBe(`/products/${shirt.id}`)
      expect(showroomCalls('GET', '/products/{product}/versions')[0]?.query).toEqual({ per_page: '10' })
      expect(showroomCalls('GET', '/families/{family}')[0]?.path).toBe('/families/shirts')
      expect(api).toHaveBeenCalledWith('/screens/showroom.product', expect.objectContaining({ query: expect.objectContaining({ record: shirt.id }) }))
      expect(wrapper.find('[data-testid="showroom-product-title"]').text()).toBe('shirt-1')
      expect(wrapper.find('[data-testid="showroom-product-status"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-product-slug"]').text()).toBe('shirt-1')
      expect(row(wrapper, 'showroom-completeness-web-en').text()).toContain('50%')
      expect((wrapper.find('[data-testid="showroom-value-name"]').element as HTMLInputElement).value).toBe('Oxford shirt')
      expect(picked(wrapper, 'showroom-value-color')).toBe('red')
    })

    it('shows the no-access state on a 403', async () => {
      showroomWith({ 'GET /products/{product}': reply(403) })

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-product-form"]').exists()).toBe(false)
    })

    it('shows the not-found state on a 404', async () => {
      showroomWith({ 'GET /products/{product}': reply(404) })

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
    })
  })

  describe('values', () => {
    it('saves the product with the old form\'s fields in one update, then reloads', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)
      await submit(wrapper)

      const [call] = showroomCalls('PATCH', '/products/{product}')
      expect(call?.path).toBe(`/products/${shirt.id}`)
      expect(call?.body).toEqual(UNCHANGED)
      expect(showroomCalls('GET', '/products/{product}')).toHaveLength(2)
    })

    it('sends the edited values of the picked slot, the family, the owner and enabled', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-value-name"]').setValue('Linen shirt')
      await pick(wrapper, 'showroom-value-color', 'blue')
      await pick(wrapper, 'showroom-product-family', 'shoes')
      await wrapper.find('[data-testid="showroom-product-owner"]').setValue('  ')
      await pick(wrapper, 'showroom-product-enabled-input', false)
      await submit(wrapper)

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toEqual({
        ...UNCHANGED,
        family: 'shoes',
        owner: null,
        enabled: false,
        values: {
          name: [{ locale: 'en', scope: null, data: 'Linen shirt' }],
          color: [{ locale: null, scope: null, data: 'blue' }]
        }
      })
    })

    it('edits the slot picked in the URL, and clears a value left empty', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, `${PATH}?locale=fr&channel=web`)

      expect((wrapper.find('[data-testid="showroom-value-name"]').element as HTMLInputElement).value).toBe('')
      await wrapper.find('[data-testid="showroom-value-name"]').setValue('Chemise')
      await pick(wrapper, 'showroom-value-color', null)
      await submit(wrapper)

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toMatchObject({
        values: {
          name: [{ locale: 'fr', scope: null, data: 'Chemise' }],
          color: [{ locale: null, scope: null, data: null }]
        }
      })
    })

    it('switches the slot through the URL', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)
      await pick(wrapper, 'showroom-slot-locale', 'fr')
      await wrapper.find('[data-testid="showroom-slot-picker"]').trigger('submit')
      await settle()

      expect(useRouter().currentRoute.value.query).toMatchObject({ locale: 'fr', channel: 'web' })
    })

    it('adds an attribute outside the family to the form', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)
      await pick(wrapper, 'showroom-add-attribute-select', 'weight')
      await wrapper.find('[data-testid="showroom-add-attribute"]').trigger('submit')
      await settle()
      await wrapper.find('[data-testid="showroom-value-weight"]').setValue('250')
      await submit(wrapper)

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toMatchObject({
        values: { weight: [{ locale: null, scope: null, data: 250 }] }
      })
    })

    it('shows the field errors of a 422 under their fields', async () => {
      showroomWith({ 'PATCH /products/{product}': invalid({ 'values.name.0.data': ['The name is too long.'], 'family': ['The family is invalid.'] }) })

      const wrapper = await mountShowroomPage(Page, PATH)
      await submit(wrapper)

      expect(wrapper.find('[data-testid="showroom-value-error-name"]').text()).toBe('The name is too long.')
      expect(wrapper.text()).toContain('The family is invalid.')
      expect(showroomCalls('GET', '/products/{product}')).toHaveLength(1)
    })

    it('shows the no-access notice when the update answers 403', async () => {
      showroomWith({ 'PATCH /products/{product}': reply(403) })

      const wrapper = await mountShowroomPage(Page, PATH)
      await submit(wrapper)

      expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-product-form"]').exists()).toBe(true)
    })
  })

  describe('categories, flags and external ids', () => {
    it('sends the own categories typed', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-product-categories"]').setValue('tops, summer, tops')
      await submit(wrapper)

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toMatchObject({ categories: ['tops', 'summer'] })
    })

    it('shows own and effective flags, and sends the own flags ticked', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-effective-flag="eco"]').attributes('data-inherited')).toBe('false')
      expect(wrapper.find('[data-effective-flag="sale"]').attributes('data-inherited')).toBe('true')
      expect(picked(wrapper, 'showroom-flag-eco')).toBe(true)

      await pick(wrapper, 'showroom-flag-eco', false)
      await pick(wrapper, 'showroom-flag-sale', true)
      await submit(wrapper)

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toMatchObject({ flags: ['sale'] })
    })

    it('sends the external ids, an emptied one as null and a new source', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-external-synced-erp"]').text()).toContain('synced')
      await wrapper.find('[data-testid="showroom-external-id-erp"]').setValue('')
      await wrapper.find('[data-testid="showroom-external-new-source"]').setValue('pim')
      await wrapper.find('[data-testid="showroom-external-new-id"]').setValue('P-1')
      await submit(wrapper)

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toMatchObject({ external_ids: { erp: null, pim: 'P-1' } })
    })

    it('shows a 422 on an external id under its source', async () => {
      showroomWith({ 'PATCH /products/{product}': invalid({ 'external_ids.erp': ['The erp id is taken.'] }) })

      const wrapper = await mountShowroomPage(Page, PATH)
      await submit(wrapper)

      expect(wrapper.find('[data-external-source="erp"]').text()).toContain('The erp id is taken.')
    })
  })

  describe('workflow', () => {
    it('offers the transitions open from draft and sends one with its comment, then reloads', async () => {
      showroomWith({ 'POST /products/{product}/transitions': ok({ data: { ...shirt, status: 'in_review' } }) })

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-transition-submit"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-transition-archive"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-transition-approve"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="showroom-transition-unpublish"]').exists()).toBe(false)

      await wrapper.find('[data-testid="showroom-transition-comment"]').setValue(' Ready for review ')
      await wrapper.find('[data-testid="showroom-transition-submit"]').trigger('click')
      await settle()
      await settle()

      const [call] = showroomCalls('POST', '/products/{product}/transitions')
      expect(call?.path).toBe(`/products/${shirt.id}/transitions`)
      expect(call?.body).toEqual({ transition: 'submit', comment: 'Ready for review' })
      expect(showroomCalls('GET', '/products/{product}')).toHaveLength(2)
    })

    it('sends a transition without a comment when none is typed', async () => {
      showroomWith({ 'POST /products/{product}/transitions': ok({ data: shirt }) })

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-transition-archive"]').trigger('click')
      await settle()

      expect(showroomCalls('POST', '/products/{product}/transitions')[0]?.body).toEqual({ transition: 'archive' })
    })

    it('offers unpublish only on a published product', async () => {
      showroomWith({ 'GET /products/{product}': ok({ data: { ...shirt, status: 'approved', published_version: 2, published_at: '2026-11-02T10:00:00+00:00' } }) })

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-transition-publish"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-transition-unpublish"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="showroom-published-version"]').text()).toContain('v2')
    })

    it('shows Showroom\'s message when it refuses a transition', async () => {
      showroomWith({ 'POST /products/{product}/transitions': reply(409, { message: 'The product is not complete.' }) })

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-transition-submit"]').trigger('click')
      await settle()

      expect(wrapper.find('[data-testid="showroom-action-error"]').text()).toContain('The product is not complete.')
    })

    it('shows the no-access notice when a transition answers 403', async () => {
      showroomWith({ 'POST /products/{product}/transitions': reply(403) })

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-transition-submit"]').trigger('click')
      await settle()

      expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    })
  })

  describe('versions', () => {
    it('lists the history, and reverts to an older version once confirmed, then reloads', async () => {
      showroomWith({ 'POST /products/{product}/revert': ok({ data: shirt }) })
      const confirm = confirmWith(true)

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-version-2"]').text()).toContain('Fixed the name')
      expect(wrapper.find('[data-testid="showroom-revert-2"]').exists()).toBe(false)

      await wrapper.find('[data-testid="showroom-revert-1"]').trigger('click')
      await settle()
      await settle()

      expect(confirm).toHaveBeenCalledOnce()
      const [call] = showroomCalls('POST', '/products/{product}/revert')
      expect(call?.path).toBe(`/products/${shirt.id}/revert`)
      expect(call?.body).toEqual({ version: 1 })
      expect(showroomCalls('GET', '/products/{product}/versions')).toHaveLength(2)
    })

    it('does not revert when the confirmation is declined', async () => {
      showroomWith({ 'POST /products/{product}/revert': ok({ data: shirt }) })
      confirmWith(false)

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-revert-1"]').trigger('click')
      await settle()

      expect(showroomCalls('POST', '/products/{product}/revert')).toHaveLength(0)
    })

    it('shows the no-access notice when revert answers 403', async () => {
      showroomWith({ 'POST /products/{product}/revert': reply(403) })
      confirmWith(true)

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-revert-1"]').trigger('click')
      await settle()

      expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    })
  })

  describe('assets', () => {
    it('lists the linked assets, each linking to its page', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-asset-photo-1"]').attributes('href')).toBe('/showroom/assets/photo-1')
    })

    it('uploads a file, links it to the product with its role, then reloads', async () => {
      showroomWith({
        'POST /assets': ok({ data: { code: 'photo-9' } }, 201),
        'POST /assets/{asset}/links': ok({ data: {} }, 201)
      })

      const wrapper = await mountShowroomPage(Page, PATH)
      const input = wrapper.find('[data-testid="showroom-asset-file"]')
      Object.defineProperty(input.element, 'files', { value: [new File(['jpeg'], 'front.jpg', { type: 'image/jpeg' })], configurable: true })
      await input.trigger('change')
      await wrapper.find('[data-testid="showroom-asset-role"]').setValue('front')
      await wrapper.find('[data-testid="showroom-asset-upload"]').trigger('submit')
      await settle()
      await settle()

      expect(showroomCalls('POST', '/assets')).toHaveLength(1)
      expect(String(showroomCalls('POST', '/assets')[0]?.body)).toContain('front.jpg')
      const [link] = showroomCalls('POST', '/assets/{asset}/links')
      expect(link?.path).toBe('/assets/photo-9/links')
      expect(link?.body).toEqual({ type: 'product', target: 'shirt-1', role: 'front' })
      expect(showroomCalls('GET', '/products/{product}')).toHaveLength(2)
    })

    it('shows the field errors of a 422 on upload', async () => {
      showroomWith({ 'POST /assets': invalid({ file: ['The file is too large.'] }) })

      const wrapper = await mountShowroomPage(Page, PATH)
      const input = wrapper.find('[data-testid="showroom-asset-file"]')
      Object.defineProperty(input.element, 'files', { value: [new File(['x'], 'huge.jpg', { type: 'image/jpeg' })], configurable: true })
      await input.trigger('change')
      await wrapper.find('[data-testid="showroom-asset-upload"]').trigger('submit')
      await settle()

      expect(wrapper.text()).toContain('The file is too large.')
      expect(showroomCalls('POST', '/assets/{asset}/links')).toHaveLength(0)
    })
  })

  describe('associations', () => {
    it('lists the associations and removes an own target through the update', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)

      expect(wrapper.find('[data-testid="showroom-association-related-shirt-2"] a').attributes('href')).toBe('/showroom/products?search=shirt-2')
      await wrapper.find('[data-testid="showroom-association-remove-related-shirt-2"]').trigger('click')
      await settle()
      await settle()

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toEqual({ associations: { related: { products: [], product_models: [] } } })
      expect(showroomCalls('GET', '/products/{product}')).toHaveLength(2)
    })

    it('adds a target of a plain type, keeping the type\'s own targets', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)
      await pick(wrapper, 'showroom-association-type', 'related')
      await pick(wrapper, 'showroom-association-kind', 'product_models')
      await wrapper.find('[data-testid="showroom-association-target"]').setValue('oxford')
      await wrapper.find('[data-testid="showroom-association-add"]').trigger('submit')
      await settle()

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toEqual({ associations: { related: { products: ['shirt-2'], product_models: ['oxford'] } } })
    })

    it('adds a target of a quantified type with its quantity', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, PATH)
      await pick(wrapper, 'showroom-association-type', 'bundle')
      await wrapper.find('[data-testid="showroom-association-target"]').setValue('shirt-3')
      await wrapper.find('[data-testid="showroom-association-quantity"]').setValue('2')
      await wrapper.find('[data-testid="showroom-association-add"]').trigger('submit')
      await settle()

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toEqual({
        quantified_associations: { bundle: { products: [{ identifier: 'shirt-3', quantity: 2 }], product_models: [] } }
      })
    })

    it('shows the field errors of a 422 on an association', async () => {
      showroomWith({ 'PATCH /products/{product}': invalid({ 'associations.related.products.1': ['The product shirt-404 does not exist.'] }) })

      const wrapper = await mountShowroomPage(Page, PATH)
      await pick(wrapper, 'showroom-association-type', 'related')
      await wrapper.find('[data-testid="showroom-association-target"]').setValue('shirt-404')
      await wrapper.find('[data-testid="showroom-association-add"]').trigger('submit')
      await settle()

      expect(wrapper.find('[data-testid="showroom-associations"]').text()).toContain('The product shirt-404 does not exist.')
    })
  })

  describe('delete', () => {
    it('deletes the product once confirmed, then returns to the list', async () => {
      showroomWith({ 'DELETE /products/{product}': reply(204) })
      confirmWith(true)

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-product-delete"]').trigger('click')
      await settle()

      const [call] = showroomCalls('DELETE', '/products/{product}')
      expect(call?.path).toBe(`/products/${shirt.id}`)
      expect(call?.body).toBeUndefined()
      await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/products'))
    })

    it('does not delete when the confirmation is declined', async () => {
      showroomWith({ 'DELETE /products/{product}': reply(204) })
      confirmWith(false)

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-product-delete"]').trigger('click')
      await settle()

      expect(showroomCalls('DELETE', '/products/{product}')).toHaveLength(0)
    })

    it('shows the no-access notice when delete answers 403, and stays', async () => {
      showroomWith({ 'DELETE /products/{product}': reply(403) })
      confirmWith(true)

      const wrapper = await mountShowroomPage(Page, PATH)
      await wrapper.find('[data-testid="showroom-product-delete"]').trigger('click')
      await settle()

      expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
      expect(useRouter().currentRoute.value.path).toBe(PATH)
    })
  })

  describe('a variant product', () => {
    const VARIANT = `/showroom/products/${oxfordSmall.id}`

    it('loads its product model and family variant, and shows what it inherits', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, VARIANT)

      expect(showroomCalls('GET', '/product-models/{productModel}')[0]?.path).toBe('/product-models/oxford')
      expect(showroomCalls('GET', '/family-variants/{familyVariant}')[0]?.path).toBe('/family-variants/shirts_by_size')
      expect(showroomCalls('GET', '/families/{family}')).toHaveLength(0)
      expect(wrapper.find('[data-testid="showroom-product-parent"]').attributes('href')).toBe('/showroom/product-models/oxford')
      expect(wrapper.find('[data-testid="showroom-product-family"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="showroom-product-inherited-owner"]').text()).toContain('acme')
      expect(wrapper.find('[data-testid="showroom-inherited-name|en|"]').text()).toBe('Oxford')
      expect(wrapper.find('[data-testid="showroom-inherited-categories"]').text()).toContain('tops')
      expect(wrapper.find('[data-testid="showroom-value-name"]').exists()).toBe(false)
      expect(picked(wrapper, 'showroom-value-color')).toBe('blue')
    })

    it('saves only what a variant owns: no family or owner, its own categories and its level\'s values', async () => {
      showroomWith()

      const wrapper = await mountShowroomPage(Page, VARIANT)
      await submit(wrapper)

      expect(showroomCalls('PATCH', '/products/{product}')[0]?.body).toEqual({
        enabled: true,
        categories: ['sale'],
        values: { color: [{ locale: null, scope: null, data: 'blue' }] },
        external_ids: { erp: 'E-1' },
        flags: []
      })
    })
  })
})
