import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/flags/[flag].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { restricted } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { pick, picked } from '../ui'

/*
 * `showroom.flag` (AC 54, 71): one flag (`flags.show`), its code shown but
 * never edited. A save (`flags.update`) sends the labels (the current
 * locale's edited, the others kept), what it applies to and its sort order.
 * Delete is confirmed; Showroom refuses to delete a flag still assigned, with
 * its message. A 422 shows the field errors, a 403 no access and a 404 not
 * found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/flags/restricted'

const flag = { ...restricted, labels: { en: 'Restricted', fr: 'Restreint' } }

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /flags/{flag}': ok({ data: flag }),
    'PATCH /flags/{flag}': ok({ data: flag }),
    'DELETE /flags/{flag}': reply(204),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function save(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-flag-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.flag', () => {
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

  it('loads the flag, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/flags/{flag}')[0]?.path).toBe('/flags/restricted')
    expect(api).toHaveBeenCalledWith('/screens/showroom.flag', expect.objectContaining({ query: expect.objectContaining({ record: 'restricted' }) }))
    expect(wrapper.find('[data-testid="showroom-flag-title"] h1').text()).toBe('Restricted')
    expect(wrapper.find('[data-testid="showroom-flag-code"]').text()).toBe('restricted')
    expect(picked(wrapper, 'showroom-flag-applies-product')).toBe(true)
    expect(picked(wrapper, 'showroom-flag-applies-owner')).toBe(false)
    expect((wrapper.find('[data-testid="showroom-flag-sort"]').element as HTMLInputElement).value).toBe('3')
  })

  it('saves the labels, subjects and sort order, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flag-label"]').setValue('Restricted sale')
    await pick(wrapper, 'showroom-flag-applies-owner', true)
    await wrapper.find('[data-testid="showroom-flag-sort"]').setValue('1')
    await save(wrapper)

    const [call] = showroomCalls('PATCH', '/flags/{flag}')
    expect(call?.path).toBe('/flags/restricted')
    expect(call?.body).toEqual({ labels: { en: 'Restricted sale', fr: 'Restreint' }, applies_to: ['product', 'owner'], sort_order: 1 })
    expect(showroomCalls('GET', '/flags/{flag}')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /flags/{flag}': invalid({ applies_to: ['The applies to field must have at least 1 items.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await pick(wrapper, 'showroom-flag-applies-product', false)
    await save(wrapper)

    expect(showroomCalls('PATCH', '/flags/{flag}')[0]?.body).toMatchObject({ applies_to: [] })
    expect(wrapper.text()).toContain('The applies to field must have at least 1 items.')
  })

  it('deletes the flag once confirmed, then returns to the list', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flag-delete"]').trigger('click')
    await settle()

    const [call] = showroomCalls('DELETE', '/flags/{flag}')
    expect(call?.path).toBe('/flags/restricted')
    expect(call?.body).toBeUndefined()
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/flags'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flag-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/flags/{flag}')).toHaveLength(0)
  })

  it('shows Showroom\'s message when a delete is refused with a 409', async () => {
    showroomWith({ 'DELETE /flags/{flag}': reply(409, { message: 'The flag is still assigned.' }) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-flag-delete"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="showroom-action-error"]').text()).toContain('The flag is still assigned.')
    expect(useRouter().currentRoute.value.path).toBe(PATH)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'PATCH /flags/{flag}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await save(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /flags/{flag}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-flag-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /flags/{flag}': reply(404, { message: 'Not found.' }) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
