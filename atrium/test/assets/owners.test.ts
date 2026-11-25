import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/owners/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { acme, brand, downtown, store } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { setField, tableRow } from '@refactor-circus/atrium/testing'

/*
 * `showroom.owners` (AC 54, 71): every owner (`owners.index`), a cursor page
 * at a time, with its type, parent and children counted, filtered by search
 * and type as v1's filters sent them. A new owner is created with its code,
 * type, parent and its label in the current locale (`owners.store`). A 422
 * shows the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/owners'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /owners': ok(cursorPage([acme, downtown], 'next-cursor')),
    'GET /owner-types': ok(cursorPage([brand, store])),
    'POST /owners': ok({ data: acme }, 201),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper, form: string): Promise<void> {
  await wrapper.find(`[data-testid="${form}"]`).trigger('submit')
  await settle()
  await settle()
}

describe('showroom.owners', () => {
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

  it('loads the owners and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/owners')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.owners', expect.anything())

    const downtownRow = tableRow(wrapper, 'showroom-owner-row-downtown')
    expect(downtownRow.find('a').attributes('href')).toBe('/showroom/owners/01JOWNERDOWNTOWN')
    expect(downtownRow.text()).toContain('Downtown')
    expect(downtownRow.text()).toContain('store')
    expect(downtownRow.text()).toContain('acme')
    expect(wrapper.find('[data-testid="showroom-owners-owner-types"]').attributes('href')).toBe('/showroom/owner-types')
    expect(wrapper.find('[data-testid="showroom-owners-flags"]').attributes('href')).toBe('/showroom/flags')
  })

  it('sends the search and type filters as the endpoint\'s query', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owners-search"]').setValue('acme')
    await setField(wrapper, 'showroom-owners-type', 'brand')
    await submit(wrapper, 'showroom-owners-filters')

    expect(showroomCalls('GET', '/owners').at(-1)?.query).toEqual({ search: 'acme', type: 'brand' })
  })

  it('reads the type filter from the route, as the owner type page links to it', async () => {
    showroomWith()

    await mountShowroomPage(Page, `${PATH}?type=store`)

    expect(showroomCalls('GET', '/owners')[0]?.query).toEqual({ type: 'store' })
  })

  it('pages with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owners-next"]').trigger('click')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/owners').at(-1)?.query).toEqual({ cursor: 'next-cursor' })
  })

  it('creates an owner with its code, type, parent and label, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-create-code"]').setValue('uptown')
    await setField(wrapper, 'showroom-owner-create-type', 'store')
    await wrapper.find('[data-testid="showroom-owner-create-parent"]').setValue('acme')
    await wrapper.find('[data-testid="showroom-owner-create-label"]').setValue('Uptown')
    await submit(wrapper, 'showroom-owner-create-form')

    const [call] = showroomCalls('POST', '/owners')
    expect(call?.path).toBe('/owners')
    expect(call?.body).toEqual({ code: 'uptown', type: 'store', parent: 'acme', labels: { en: 'Uptown' } })
    expect(showroomCalls('GET', '/owners')).toHaveLength(2)
  })

  it('leaves out a blank parent and label, so the owner is a root', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-create-code"]').setValue('globex')
    await setField(wrapper, 'showroom-owner-create-type', 'brand')
    await submit(wrapper, 'showroom-owner-create-form')

    expect(showroomCalls('POST', '/owners')[0]?.body).toEqual({ code: 'globex', type: 'brand' })
  })

  it('offers no create form until an owner type exists', async () => {
    showroomWith({ 'GET /owner-types': ok(cursorPage([])) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-owner-create-form"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="showroom-owners-no-types"]').exists()).toBe(true)
  })

  it('shows the field errors of a 422 on create', async () => {
    showroomWith({ 'POST /owners': invalid({ parent: ['A store cannot sit under a store.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-create-code"]').setValue('uptown')
    await setField(wrapper, 'showroom-owner-create-type', 'store')
    await wrapper.find('[data-testid="showroom-owner-create-parent"]').setValue('downtown')
    await submit(wrapper, 'showroom-owner-create-form')

    expect(wrapper.text()).toContain('A store cannot sit under a store.')
  })

  it('shows the no-access notice when create answers 403', async () => {
    showroomWith({ 'POST /owners': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-create-code"]').setValue('uptown')
    await setField(wrapper, 'showroom-owner-create-type', 'brand')
    await submit(wrapper, 'showroom-owner-create-form')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /owners': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-owners-table"]').exists()).toBe(false)
  })
})
