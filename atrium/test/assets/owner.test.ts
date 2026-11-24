import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/owners/[owner].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { acmeShown, brand, downtownShown, eco, pickFile, premium, sale, store } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { fieldValue, hasField, setField } from '@refactor-circus/atrium/testing'

/*
 * `showroom.owner` (AC 54, 71): one owner (`owners.show`) with its chain,
 * children, linked assets and the products it owns. A save
 * (`owners.update`) sends the labels (the current locale's edited, the
 * others kept), the parent, enabled, its external ids and own flags. A child
 * is added under it (`owners.store` with it as parent), an asset is uploaded
 * and linked to it, and delete is confirmed (`owners.destroy`). A 422 shows
 * the field errors, a 403 no access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/owners/01JOWNERACME'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /owners/{owner}': ok({ data: acmeShown }),
    'GET /owner-types': ok(cursorPage([brand, store])),
    'GET /flags': ok(cursorPage([eco, premium, sale])),
    'PATCH /owners/{owner}': ok({ data: acmeShown }),
    'POST /owners': ok({ data: downtownShown }, 201),
    'DELETE /owners/{owner}': reply(204),
    'POST /assets': ok({ data: { code: 'logo-2' } }, 201),
    'POST /assets/{asset}/links': ok({ data: {} }),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper, form: string): Promise<void> {
  await wrapper.find(`[data-testid="${form}"]`).trigger('submit')
  await settle()
  await settle()
}

describe('showroom.owner', () => {
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

  it('loads the owner, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/owners/{owner}')[0]?.path).toBe('/owners/01JOWNERACME')
    expect(api).toHaveBeenCalledWith('/screens/showroom.owner', expect.objectContaining({ query: expect.objectContaining({ record: '01JOWNERACME' }) }))
    expect(wrapper.find('[data-testid="showroom-owner-title"]').text()).toBe('Acme')
    expect(wrapper.find('[data-testid="showroom-owner-subtitle"]').text()).toBe('acme · brand')
    expect(wrapper.find('[data-testid="showroom-owner-enabled"]').text()).toBe('Enabled')
    expect(wrapper.find('[data-testid="showroom-owner-slug"]').text()).toBe('acme')
    expect(wrapper.find('[data-testid="showroom-owner-products"]').attributes('href')).toBe('/showroom/products?owner=acme')
    expect(wrapper.find('[data-testid="showroom-owner-child-downtown"]').attributes('href')).toBe('/showroom/owners?search=downtown')
    expect(wrapper.find('[data-testid="showroom-asset-hero"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-owner-counts"]').text()).toContain('3')
    expect(hasField(wrapper, 'showroom-flag-premium')).toBe(true)
    expect(hasField(wrapper, 'showroom-flag-sale')).toBe(false)
    expect(fieldValue(wrapper, 'showroom-flag-eco')).toBe(true)
  })

  it('shows the chain from the root, each ancestor linking to the owner list searched for it', async () => {
    showroomWith({ 'GET /owners/{owner}': ok({ data: downtownShown }) })

    const wrapper = await mountShowroomPage(Page, '/showroom/owners/01JOWNERDOWNTOWN')

    const chain = wrapper.find('[data-testid="showroom-owner-chain"]')
    expect(chain.find('a').attributes('href')).toBe('/showroom/owners?search=acme')
    expect(chain.text()).toContain('downtown')
    expect(chain.findAll('a')).toHaveLength(1)
  })

  it('saves the labels, parent, enabled, external ids and flags, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-label"]').setValue('Acme Corp')
    await wrapper.find('[data-testid="showroom-owner-parent"]').setValue('holding')
    await setField(wrapper, 'showroom-owner-enabled-input', false)
    await wrapper.find('[data-testid="showroom-external-id-erp"]').setValue('A-2')
    await setField(wrapper, 'showroom-flag-premium', true)
    await submit(wrapper, 'showroom-owner-form')

    const [call] = showroomCalls('PATCH', '/owners/{owner}')
    expect(call?.path).toBe('/owners/01JOWNERACME')
    expect(call?.body).toEqual({
      labels: { en: 'Acme Corp', fr: 'Acmé' },
      parent: 'holding',
      enabled: false,
      external_ids: { erp: 'A-2' },
      flags: ['eco', 'premium']
    })
    expect(showroomCalls('GET', '/owners/{owner}')).toHaveLength(2)
  })

  it('sends a blank parent as null, which makes the owner a root', async () => {
    showroomWith({ 'GET /owners/{owner}': ok({ data: downtownShown }) })

    const wrapper = await mountShowroomPage(Page, '/showroom/owners/01JOWNERDOWNTOWN')
    await wrapper.find('[data-testid="showroom-owner-parent"]').setValue('')
    await submit(wrapper, 'showroom-owner-form')

    expect(showroomCalls('PATCH', '/owners/{owner}')[0]?.body).toMatchObject({ parent: null })
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /owners/{owner}': invalid({ parent: ['An owner cannot move under its own descendant.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-parent"]').setValue('downtown')
    await submit(wrapper, 'showroom-owner-form')

    expect(wrapper.text()).toContain('An owner cannot move under its own descendant.')
  })

  it('adds a child owner under it, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-child-code"]').setValue('uptown')
    await setField(wrapper, 'showroom-owner-child-type', 'store')
    await submit(wrapper, 'showroom-owner-child-form')

    expect(showroomCalls('POST', '/owners')[0]?.body).toEqual({ code: 'uptown', type: 'store', parent: 'acme' })
    expect(showroomCalls('GET', '/owners/{owner}')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on adding a child', async () => {
    showroomWith({ 'POST /owners': invalid({ code: ['The code has already been taken.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-child-code"]').setValue('downtown')
    await setField(wrapper, 'showroom-owner-child-type', 'store')
    await submit(wrapper, 'showroom-owner-child-form')

    expect(wrapper.text()).toContain('The code has already been taken.')
  })

  it('uploads an asset and links it to the owner by its code', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await pickFile(wrapper.find('[data-testid="showroom-asset-file"]'), new File(['png'], 'logo.png', { type: 'image/png' }))
    await wrapper.find('[data-testid="showroom-asset-role"]').setValue('logo')
    await submit(wrapper, 'showroom-asset-upload')

    expect(String(showroomCalls('POST', '/assets')[0]?.body)).toContain('logo.png')
    const [link] = showroomCalls('POST', '/assets/{asset}/links')
    expect(link?.path).toBe('/assets/logo-2/links')
    expect(link?.body).toEqual({ type: 'owner', target: 'acme', role: 'logo' })
  })

  it('deletes the owner once confirmed, then returns to the list', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/owners/{owner}')[0]?.path).toBe('/owners/01JOWNERACME')
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/owners'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/owners/{owner}')).toHaveLength(0)
  })

  it('shows Showroom\'s message when a delete is refused with a 409', async () => {
    showroomWith({ 'DELETE /owners/{owner}': reply(409, { message: 'The owner still has children.' }) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-owner-delete"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="showroom-action-error"]').text()).toContain('The owner still has children.')
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'PATCH /owners/{owner}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await submit(wrapper, 'showroom-owner-form')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /owners/{owner}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-owner-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /owners/{owner}': reply(404, { message: 'Not found.' }) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
