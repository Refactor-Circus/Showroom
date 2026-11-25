import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/families/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, shirts, shoes, showroomCalls } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { tableRow } from '@refactor-circus/atrium/testing'

/*
 * `showroom.families` (AC 54, 71): every family (`families.index`), a cursor
 * page at a time, with v1's search sent as the endpoint's query. A new family
 * is created with its code, its label in the current locale and its sort
 * order (`families.store`). A 422 shows the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/families'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /families': ok(cursorPage([shirts, shoes], 'next-cursor')),
    ...overrides
  })
}

describe('showroom.families', () => {
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

  it('loads the families and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/families')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.families', expect.anything())
    expect(tableRow(wrapper, 'showroom-family-row-shirts').find('a').attributes('href')).toBe('/showroom/families/shirts')
    expect(tableRow(wrapper, 'showroom-family-row-shirts').text()).toContain('Shirts')
    expect(tableRow(wrapper, 'showroom-family-row-shirts').text()).toContain('name')
  })

  it('sends the search as the endpoint\'s query, and pages with its cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-families-search"]').setValue('shi')
    await wrapper.find('[data-testid="showroom-families-filters"]').trigger('submit')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/families').at(-1)?.query).toEqual({ search: 'shi' })

    await wrapper.find('[data-testid="showroom-families-next"]').trigger('click')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/families').at(-1)?.query).toEqual({ search: 'shi', cursor: 'next-cursor' })
  })

  it('creates a family with its code, label and sort order, then reloads', async () => {
    showroomWith({ 'POST /families': ok({ data: shirts }, 201) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-create-code"]').setValue('hats')
    await wrapper.find('[data-testid="showroom-family-create-label"]').setValue('Hats')
    await wrapper.find('[data-testid="showroom-family-create-sort"]').setValue('4')
    await wrapper.find('[data-testid="showroom-family-create-form"]').trigger('submit')
    await settle()

    const [call] = showroomCalls('POST', '/families')
    expect(call?.path).toBe('/families')
    expect(call?.body).toEqual({ code: 'hats', labels: { en: 'Hats' }, sort_order: 4 })
    expect(showroomCalls('GET', '/families')).toHaveLength(2)
  })

  it('leaves the labels out when none is typed', async () => {
    showroomWith({ 'POST /families': ok({ data: shirts }, 201) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-create-code"]').setValue('hats')
    await wrapper.find('[data-testid="showroom-family-create-form"]').trigger('submit')
    await settle()

    expect(showroomCalls('POST', '/families')[0]?.body).toEqual({ code: 'hats', sort_order: 0 })
  })

  it('shows the field errors of a 422 on create', async () => {
    showroomWith({ 'POST /families': invalid({ code: ['The code format is invalid.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-create-code"]').setValue('Hats!')
    await wrapper.find('[data-testid="showroom-family-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.text()).toContain('The code format is invalid.')
  })

  it('shows the no-access notice when create answers 403', async () => {
    showroomWith({ 'POST /families': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-family-create-code"]').setValue('hats')
    await wrapper.find('[data-testid="showroom-family-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /families': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-families-table"]').exists()).toBe(false)
  })
})
