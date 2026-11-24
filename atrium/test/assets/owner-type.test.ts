import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/owner-types/[ownerType].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { brand, store } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { pick, picked } from '../ui'

/*
 * `showroom.owner-type` (AC 54, 71): one owner type (`owner-types.show`) and
 * its chain rules. A save (`owner-types.update`) sends the labels (the
 * current locale's edited, the others kept), the parent types (null for any)
 * and both switches; delete is confirmed (`owner-types.destroy`), and
 * Showroom refuses it with a message while owners use the type. A 422 shows
 * the field errors, a 403 no access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/owner-types/store'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /owner-types/{ownerType}': ok({ data: store }),
    'GET /owner-types': ok(cursorPage([brand, store])),
    'PATCH /owner-types/{ownerType}': ok({ data: store }),
    'DELETE /owner-types/{ownerType}': reply(204),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function save(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-owner-type-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.owner-type', () => {
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

  it('loads the type and its rules, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/owner-types/{ownerType}')[0]?.path).toBe('/owner-types/store')
    expect(api).toHaveBeenCalledWith('/screens/showroom.owner-type', expect.objectContaining({ query: expect.objectContaining({ record: 'store' }) }))
    expect(wrapper.find('[data-testid="showroom-owner-type-title"]').text()).toBe('Store')
    expect(wrapper.find('[data-testid="showroom-owner-type-owners"]').attributes('href')).toBe('/showroom/owners?type=store')
    expect(picked(wrapper, 'showroom-owner-type-any-parent')).toBe(false)
    expect(picked(wrapper, 'showroom-owner-type-parent-brand')).toBe(true)
    expect(picked(wrapper, 'showroom-owner-type-root')).toBe(false)
    expect(picked(wrapper, 'showroom-owner-type-owns')).toBe(false)
  })

  it('saves the labels and rules, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-type-label"]').setValue('Shop')
    await pick(wrapper, 'showroom-owner-type-parent-store', true)
    await pick(wrapper, 'showroom-owner-type-owns', true)
    await save(wrapper)

    const [call] = showroomCalls('PATCH', '/owner-types/{ownerType}')
    expect(call?.path).toBe('/owner-types/store')
    expect(call?.body).toEqual({ labels: { en: 'Shop' }, parent_types: ['brand', 'store'], can_be_root: false, owns_products: true })
    expect(showroomCalls('GET', '/owner-types/{ownerType}')).toHaveLength(2)
  })

  it('sends parent_types null when any parent is allowed', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await pick(wrapper, 'showroom-owner-type-any-parent', true)
    await save(wrapper)

    expect(showroomCalls('PATCH', '/owner-types/{ownerType}')[0]?.body).toMatchObject({ parent_types: null })
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /owner-types/{ownerType}': invalid({ 'parent_types.0': ['The selected parent type is invalid.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await save(wrapper)

    expect(wrapper.text()).toContain('The selected parent type is invalid.')
  })

  it('deletes the type once confirmed, then returns to the list', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-type-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/owner-types/{ownerType}')[0]?.path).toBe('/owner-types/store')
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/owner-types'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-type-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/owner-types/{ownerType}')).toHaveLength(0)
  })

  it('shows Showroom\'s message when a delete is refused with a 409', async () => {
    showroomWith({ 'DELETE /owner-types/{ownerType}': reply(409, { message: 'Owners still use this type.' }) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-type-delete"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="showroom-action-error"]').text()).toContain('Owners still use this type.')
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'PATCH /owner-types/{ownerType}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await save(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /owner-types/{ownerType}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-owner-type-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /owner-types/{ownerType}': reply(404, { message: 'Not found.' }) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
