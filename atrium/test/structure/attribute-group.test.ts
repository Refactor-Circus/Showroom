import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/attribute-groups/[group].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { looks, specs } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { tableRow } from '@refactor-circus/atrium/testing'

/*
 * `showroom.attribute-group` (AC 54, 71): one attribute group
 * (`attribute-groups.show`) and the attributes in it. A save
 * (`attribute-groups.update`) sends the labels (the current locale's edited,
 * the others kept) and the sort order; delete is confirmed. A 422 shows the
 * field errors, a 403 no access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/attribute-groups/looks'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /attribute-groups/{group}': ok({ data: looks }),
    'PATCH /attribute-groups/{group}': ok({ data: looks }),
    'DELETE /attribute-groups/{group}': reply(204),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function save(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-group-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.attribute-group', () => {
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

  it('loads the group and its attributes, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/attribute-groups/{group}')[0]?.path).toBe('/attribute-groups/looks')
    expect(api).toHaveBeenCalledWith('/screens/showroom.attribute-group', expect.objectContaining({ query: expect.objectContaining({ record: 'looks' }) }))
    expect(wrapper.find('[data-testid="showroom-group-title"] h1').text()).toBe('Looks')

    const row = tableRow(wrapper, 'showroom-group-attribute-color')
    expect(row.find('a').attributes('href')).toBe('/showroom/attributes/color')
    expect(row.text()).toContain('Color')
    expect(row.text()).toContain('select')
  })

  it('shows the empty state for a group without attributes', async () => {
    showroomWith({ 'GET /attribute-groups/{group}': ok({ data: { ...specs, attributes: [] } }) })

    const wrapper = await mountShowroomPage(Page, '/showroom/attribute-groups/specs')

    expect(wrapper.find('[data-testid="showroom-group-attributes-empty"]').exists()).toBe(true)
  })

  it('saves the labels and sort order, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-group-label"]').setValue('Appearance')
    await wrapper.find('[data-testid="showroom-group-sort"]').setValue('9')
    await save(wrapper)

    const [call] = showroomCalls('PATCH', '/attribute-groups/{group}')
    expect(call?.path).toBe('/attribute-groups/looks')
    expect(call?.body).toEqual({ labels: { en: 'Appearance', fr: 'Apparence' }, sort_order: 9 })
    expect(showroomCalls('GET', '/attribute-groups/{group}')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /attribute-groups/{group}': invalid({ sort_order: ['The sort order field must be at least 0.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-group-sort"]').setValue('-1')
    await save(wrapper)

    expect(wrapper.text()).toContain('The sort order field must be at least 0.')
  })

  it('deletes the group once confirmed, then returns to the list', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-group-delete"]').trigger('click')
    await settle()

    const [call] = showroomCalls('DELETE', '/attribute-groups/{group}')
    expect(call?.path).toBe('/attribute-groups/looks')
    expect(call?.body).toBeUndefined()
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/attribute-groups'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-group-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/attribute-groups/{group}')).toHaveLength(0)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'DELETE /attribute-groups/{group}': reply(403) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-group-delete"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /attribute-groups/{group}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-group-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /attribute-groups/{group}': reply(404, { message: 'Not found.' }) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
