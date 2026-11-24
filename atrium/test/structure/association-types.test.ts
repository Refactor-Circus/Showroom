import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/association-types/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { bundle, related } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.association-types` (AC 54, 71): every association type
 * (`association-types.index`), a cursor page at a time, with its switches and
 * how many associations use it. A new type is created with its code, its
 * label in the current locale and its two switches
 * (`association-types.store`); a type is deleted from its row once confirmed
 * (`association-types.destroy`). A 422 shows the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/association-types'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /association-types': ok(cursorPage([related, bundle], 'next-cursor')),
    'POST /association-types': ok({ data: related }, 201),
    'DELETE /association-types/{associationType}': reply(204),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-association-type-create-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.association-types', () => {
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

  it('loads the types and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/association-types')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.association-types', expect.anything())

    const row = wrapper.find('[data-testid="showroom-association-type-row-related"]')
    expect(row.text()).toContain('Related')
    expect(row.text()).toContain('Two-way')
    expect(row.text()).toContain('4')
    expect(wrapper.find('[data-testid="showroom-association-type-row-bundle"]').text()).toContain('Quantified')
  })

  it('pages with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-association-types-next"]').trigger('click')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/association-types').at(-1)?.query).toEqual({ cursor: 'next-cursor' })
  })

  it('creates a type with its code, label and switches, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-association-type-create-code"]').setValue('kit')
    await wrapper.find('[data-testid="showroom-association-type-create-label"]').setValue('Kit')
    await wrapper.find('[data-testid="showroom-association-type-create-quantified"]').setValue(true)
    await submit(wrapper)

    const [call] = showroomCalls('POST', '/association-types')
    expect(call?.path).toBe('/association-types')
    expect(call?.body).toEqual({ code: 'kit', labels: { en: 'Kit' }, is_two_way: false, is_quantified: true })
    expect(showroomCalls('GET', '/association-types')).toHaveLength(2)
  })

  it('leaves the labels out when none is typed', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-association-type-create-code"]').setValue('compatible')
    await wrapper.find('[data-testid="showroom-association-type-create-two-way"]').setValue(true)
    await submit(wrapper)

    expect(showroomCalls('POST', '/association-types')[0]?.body).toEqual({ code: 'compatible', is_two_way: true, is_quantified: false })
  })

  it('shows the field errors of a 422 on create', async () => {
    showroomWith({ 'POST /association-types': invalid({ code: ['The code has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-association-type-create-code"]').setValue('related')
    await submit(wrapper)

    expect(wrapper.text()).toContain('The code has already been taken.')
  })

  it('deletes a type once confirmed, then reloads', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-association-type-delete-bundle"]').trigger('click')
    await settle()
    await settle()

    const [call] = showroomCalls('DELETE', '/association-types/{associationType}')
    expect(call?.path).toBe('/association-types/bundle')
    expect(call?.body).toBeUndefined()
    expect(showroomCalls('GET', '/association-types')).toHaveLength(2)
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-association-type-delete-bundle"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/association-types/{associationType}')).toHaveLength(0)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'DELETE /association-types/{associationType}': reply(403) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-association-type-delete-bundle"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /association-types': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-association-types-table"]').exists()).toBe(false)
  })
})
