import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/owner-types/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { brand, store } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { setField, tableRow } from '@refactor-circus/atrium/testing'

/*
 * `showroom.owner-types` (AC 54, 71): every owner type (`owner-types.index`),
 * a cursor page at a time, with the parent types it allows and its owners
 * counted. A new type is created with its code, its label in the current
 * locale and its chain rules (`owner-types.store`): any parent (sent as
 * `parent_types: null`) or only the ticked ones, whether it may be a root and
 * whether it owns products. A 422 shows the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/owner-types'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /owner-types': call => ok(call.query.per_page ? cursorPage([brand, store]) : cursorPage([brand, store], 'next-cursor')),
    'POST /owner-types': ok({ data: brand }, 201),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-owner-type-create-form"]').trigger('submit')
  await settle()
  await settle()
}

function listCalls() {
  return showroomCalls('GET', '/owner-types').filter(call => !call.query.per_page)
}

describe('showroom.owner-types', () => {
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

  it('loads the owner types with their parent rules and owner counts, and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(listCalls()[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.owner-types', expect.anything())

    const brandRow = tableRow(wrapper, 'showroom-owner-type-row-brand')
    expect(brandRow.find('a').attributes('href')).toBe('/showroom/owner-types/brand')
    expect(brandRow.text()).toContain('Brand')
    expect(brandRow.text()).toContain('Any')
    expect(brandRow.text()).toContain('2')
    expect(tableRow(wrapper, 'showroom-owner-type-row-store').text()).toContain('brand')
  })

  it('pages with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-types-next"]').trigger('click')
    await settle()
    await settle()

    expect(listCalls().at(-1)?.query).toEqual({ cursor: 'next-cursor' })
  })

  it('creates a type allowing any parent by default, a root that owns products, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-type-create-code"]').setValue('region')
    await wrapper.find('[data-testid="showroom-owner-type-create-label"]').setValue('Region')
    await submit(wrapper)

    const [call] = showroomCalls('POST', '/owner-types')
    expect(call?.path).toBe('/owner-types')
    expect(call?.body).toEqual({ code: 'region', labels: { en: 'Region' }, parent_types: null, can_be_root: true, owns_products: true })
    expect(listCalls()).toHaveLength(2)
  })

  it('creates a type restricted to the ticked parent types', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-type-create-code"]').setValue('kiosk')
    await setField(wrapper, 'showroom-owner-type-create-any-parent', false)
    await setField(wrapper, 'showroom-owner-type-create-parent-store', true)
    await setField(wrapper, 'showroom-owner-type-create-root', false)
    await setField(wrapper, 'showroom-owner-type-create-owns', false)
    await submit(wrapper)

    expect(showroomCalls('POST', '/owner-types')[0]?.body).toEqual({ code: 'kiosk', parent_types: ['store'], can_be_root: false, owns_products: false })
  })

  it('shows the field errors of a 422 on create', async () => {
    showroomWith({ 'POST /owner-types': invalid({ code: ['The code has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-type-create-code"]').setValue('brand')
    await submit(wrapper)

    expect(wrapper.text()).toContain('The code has already been taken.')
  })

  it('shows the no-access notice when create answers 403', async () => {
    showroomWith({ 'POST /owner-types': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-type-create-code"]').setValue('region')
    await submit(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /owner-types': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-owner-types-table"]').exists()).toBe(false)
  })
})
