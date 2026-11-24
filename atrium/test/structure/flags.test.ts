import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/flags/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { clearance, restricted } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { pick, row as rowOf } from '../ui'

/*
 * `showroom.flags` (AC 54, 71): every flag definition (`flags.index`), a
 * cursor page at a time, with what it applies to. A new flag is created with
 * its code, its label in the current locale, what it applies to (both, as v1
 * ticked them) and its sort order (`flags.store`). A 422 shows the field
 * errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/flags'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /flags': ok(cursorPage([clearance, restricted], 'next-cursor')),
    'POST /flags': ok({ data: clearance }, 201),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-flag-create-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.flags', () => {
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

  it('loads the flags and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/flags')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.flags', expect.anything())

    const row = rowOf(wrapper, 'showroom-flag-row-clearance')
    expect(row.find('a').attributes('href')).toBe('/showroom/flags/clearance')
    expect(row.text()).toContain('Clearance')
    expect(row.text()).toContain('Products')
    expect(row.text()).toContain('Owners')
    expect(rowOf(wrapper, 'showroom-flag-row-restricted').text()).not.toContain('Owners')
  })

  it('pages with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flags-next"]').trigger('click')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/flags').at(-1)?.query).toEqual({ cursor: 'next-cursor' })
  })

  it('creates a flag with its code, label, subjects and sort order, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flag-create-code"]').setValue('pricing_only')
    await wrapper.find('[data-testid="showroom-flag-create-label"]').setValue('Pricing only')
    await pick(wrapper, 'showroom-flag-create-applies-owner', false)
    await wrapper.find('[data-testid="showroom-flag-create-sort"]').setValue('5')
    await submit(wrapper)

    const [call] = showroomCalls('POST', '/flags')
    expect(call?.path).toBe('/flags')
    expect(call?.body).toEqual({ code: 'pricing_only', labels: { en: 'Pricing only' }, applies_to: ['product'], sort_order: 5 })
    expect(showroomCalls('GET', '/flags')).toHaveLength(2)
  })

  it('applies a new flag to both subjects, and leaves the labels and sort order out, by default', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flag-create-code"]').setValue('eco')
    await submit(wrapper)

    expect(showroomCalls('POST', '/flags')[0]?.body).toEqual({ code: 'eco', applies_to: ['product', 'owner'] })
  })

  it('shows the field errors of a 422 on create', async () => {
    showroomWith({ 'POST /flags': invalid({ applies_to: ['The applies to field is required.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flag-create-code"]').setValue('eco')
    await pick(wrapper, 'showroom-flag-create-applies-product', false)
    await pick(wrapper, 'showroom-flag-create-applies-owner', false)
    await submit(wrapper)

    expect(showroomCalls('POST', '/flags')[0]?.body).toMatchObject({ applies_to: [] })
    expect(wrapper.text()).toContain('The applies to field is required.')
  })

  it('shows the no-access notice when create answers 403', async () => {
    showroomWith({ 'POST /flags': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flag-create-code"]').setValue('eco')
    await submit(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /flags': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-flags-table"]').exists()).toBe(false)
  })
})
