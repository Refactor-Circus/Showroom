import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/family-variants/[familyVariant].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { bySize, confirmWith, cursorPage, mountShowroomPage, ok, oxford, resetShowroomPages, screensApi, showroomCalls } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { row } from '../ui'

/*
 * `showroom.family-variant` (AC 54, 71): one family variant
 * (`family-variants.show`): its common attributes and each level's axes and
 * attributes, its product models (`product-models.index?family_variant=`), a
 * link to its family, and delete (`family-variants.destroy`, confirmed). A
 * 403 shows no access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/family-variants/shirts_by_size'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /family-variants/{familyVariant}': ok({ data: bySize }),
    'GET /product-models': ok(cursorPage([oxford])),
    ...overrides
  })
}

describe('showroom.family-variant', () => {
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

  it('loads the variant, its levels and its product models, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/family-variants/{familyVariant}')[0]?.path).toBe('/family-variants/shirts_by_size')
    expect(showroomCalls('GET', '/product-models')[0]?.query).toEqual({ family_variant: 'shirts_by_size', per_page: '100' })
    expect(api).toHaveBeenCalledWith('/screens/showroom.family-variant', expect.objectContaining({ query: expect.objectContaining({ record: 'shirts_by_size' }) }))
    expect(wrapper.find('[data-testid="showroom-family-variant-title"]').text()).toBe('Shirts by size')
    expect(row(wrapper, 'showroom-level-common').text()).toContain('name')
    expect(row(wrapper, 'showroom-level-1').text()).toContain('size')
    expect(row(wrapper, 'showroom-level-1').text()).toContain('color')
    expect(wrapper.find('[data-testid="showroom-family-variant-family"]').attributes('href')).toBe('/showroom/families/shirts')
    expect(wrapper.find('[data-testid="showroom-family-variant-model-oxford"]').attributes('href')).toBe('/showroom/product-models/oxford')
  })

  it('deletes the variant once confirmed, then returns to its family', async () => {
    showroomWith({ 'DELETE /family-variants/{familyVariant}': reply(204) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-variant-delete"]').trigger('click')
    await settle()

    const [call] = showroomCalls('DELETE', '/family-variants/{familyVariant}')
    expect(call?.path).toBe('/family-variants/shirts_by_size')
    expect(call?.body).toBeUndefined()
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/families/shirts'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith({ 'DELETE /family-variants/{familyVariant}': reply(204) })
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-variant-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/family-variants/{familyVariant}')).toHaveLength(0)
  })

  it('shows Showroom\'s message when delete is refused', async () => {
    showroomWith({ 'DELETE /family-variants/{familyVariant}': reply(409, { message: 'The family variant has product models.' }) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-variant-delete"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="showroom-action-error"]').text()).toContain('The family variant has product models.')
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })

  it('shows the no-access notice when delete answers 403', async () => {
    showroomWith({ 'DELETE /family-variants/{familyVariant}': reply(403) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-variant-delete"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /family-variants/{familyVariant}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-family-variant-levels"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /family-variants/{familyVariant}': reply(404) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
