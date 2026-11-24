import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/attributes/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { color, looks, name, specs } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.attributes` (AC 54, 71): every attribute (`attributes.index`), a
 * cursor page at a time, with v1's search, type and group filters sent as the
 * endpoint's query. The groups come from `attribute-groups.index`. A 403 shows
 * no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/attributes'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /attributes': ok(cursorPage([name, color], 'next-cursor')),
    'GET /attribute-groups': ok(cursorPage([looks, specs])),
    ...overrides
  })
}

async function reloaded(): Promise<void> {
  await settle()
  await settle()
}

describe('showroom.attributes', () => {
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

  it('loads the attributes and the groups, and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/attributes')[0]?.query).toEqual({})
    expect(showroomCalls('GET', '/attribute-groups')[0]?.query).toEqual({ per_page: '100' })
    expect(api).toHaveBeenCalledWith('/screens/showroom.attributes', expect.anything())

    const row = wrapper.find('[data-testid="showroom-attribute-row-color"]')
    expect(row.find('a').attributes('href')).toBe('/showroom/attributes/color')
    expect(row.text()).toContain('Color')
    expect(row.text()).toContain('select')
    expect(row.text()).toContain('Looks')
    expect(row.text()).toContain('Scopable')
    expect(wrapper.find('[data-testid="showroom-attribute-row-name"]').text()).toContain('Unique')
    expect(wrapper.find('[data-testid="showroom-attribute-new"]').attributes('href')).toBe('/showroom/attributes/create')
  })

  it('sends the search, type and group as the endpoint\'s query, and pages with its cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-attributes-search"]').setValue('col')
    await wrapper.find('[data-testid="showroom-attributes-type"]').setValue('select')
    await wrapper.find('[data-testid="showroom-attributes-group"]').setValue('looks')
    await wrapper.find('[data-testid="showroom-attributes-filters"]').trigger('submit')
    await reloaded()

    expect(showroomCalls('GET', '/attributes').at(-1)?.query).toEqual({ search: 'col', type: 'select', group: 'looks' })

    await wrapper.find('[data-testid="showroom-attributes-next"]').trigger('click')
    await reloaded()

    expect(showroomCalls('GET', '/attributes').at(-1)?.query).toEqual({ search: 'col', type: 'select', group: 'looks', cursor: 'next-cursor' })
  })

  it('reads the filters from the URL, and clears them', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, `${PATH}?type=text&group=specs`)

    expect(showroomCalls('GET', '/attributes')[0]?.query).toEqual({ type: 'text', group: 'specs' })
    expect((wrapper.find('[data-testid="showroom-attributes-type"]').element as HTMLSelectElement).value).toBe('text')

    await wrapper.find('[data-testid="showroom-attributes-clear"]').trigger('click')
    await reloaded()

    expect(showroomCalls('GET', '/attributes').at(-1)?.query).toEqual({})
  })

  it('shows the empty state when nothing matches', async () => {
    showroomWith({ 'GET /attributes': ok(cursorPage([])) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-attributes-empty"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /attributes': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-attributes-table"]').exists()).toBe(false)
  })
})
