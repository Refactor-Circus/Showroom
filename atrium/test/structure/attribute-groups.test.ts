import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/attribute-groups/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { looks, specs } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { row as rowOf } from '../ui'

/*
 * `showroom.attribute-groups` (AC 54, 71): every attribute group
 * (`attribute-groups.index`), a cursor page at a time, with how many
 * attributes each holds. A new group is created with its code, its label in
 * the current locale and its sort order (`attribute-groups.store`). A 422
 * shows the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/attribute-groups'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /attribute-groups': ok(cursorPage([looks, specs], 'next-cursor')),
    'POST /attribute-groups': ok({ data: looks }, 201),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function create(wrapper: Wrapper, code: string, label?: string, sort?: string): Promise<void> {
  await wrapper.find('[data-testid="showroom-group-create-code"]').setValue(code)

  if (label !== undefined) {
    await wrapper.find('[data-testid="showroom-group-create-label"]').setValue(label)
  }

  if (sort !== undefined) {
    await wrapper.find('[data-testid="showroom-group-create-sort"]').setValue(sort)
  }

  await wrapper.find('[data-testid="showroom-group-create-form"]').trigger('submit')
  await settle()
}

describe('showroom.attribute-groups', () => {
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

  it('loads the groups and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/attribute-groups')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.attribute-groups', expect.anything())

    const row = rowOf(wrapper, 'showroom-group-row-looks')
    expect(row.find('a').attributes('href')).toBe('/showroom/attribute-groups/looks')
    expect(row.text()).toContain('Looks')
    expect(row.text()).toContain('1')
    expect(row.text()).toContain('2')
  })

  it('pages with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-attribute-groups-next"]').trigger('click')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/attribute-groups').at(-1)?.query).toEqual({ cursor: 'next-cursor' })
  })

  it('creates a group with its code, label and sort order, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await create(wrapper, 'care', 'Care', '5')

    const [call] = showroomCalls('POST', '/attribute-groups')
    expect(call?.path).toBe('/attribute-groups')
    expect(call?.body).toEqual({ code: 'care', labels: { en: 'Care' }, sort_order: 5 })
    expect(showroomCalls('GET', '/attribute-groups')).toHaveLength(2)
  })

  it('leaves the labels out when none is typed, and sends v1\'s sort order of 0', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await create(wrapper, 'care')

    expect(showroomCalls('POST', '/attribute-groups')[0]?.body).toEqual({ code: 'care', sort_order: 0 })
  })

  it('shows the field errors of a 422 on create', async () => {
    showroomWith({ 'POST /attribute-groups': invalid({ code: ['The code has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await create(wrapper, 'looks')

    expect(wrapper.text()).toContain('The code has already been taken.')
  })

  it('shows the no-access notice when create answers 403', async () => {
    showroomWith({ 'POST /attribute-groups': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await create(wrapper, 'care')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /attribute-groups': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-attribute-groups-table"]').exists()).toBe(false)
  })
})
