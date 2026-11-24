import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/channels/[channel].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { english, french, german, master, web, webshop } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { pick, picked } from '../ui'

/*
 * `showroom.channel` (AC 54, 71): one channel (`channels.show`) with its
 * locales. A save (`channels.update`) sends the labels (the current locale's
 * edited, the others kept), the locales ticked from `locales.index`, the
 * currencies and the category tree. Delete is confirmed. A 422 shows the
 * field errors, a 403 no access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/channels/webshop'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /channels/{channel}': ok({ data: webshop }),
    'GET /locales': ok(cursorPage([english, french, german])),
    'GET /categories': ok(cursorPage([master, web])),
    'PATCH /channels/{channel}': ok({ data: webshop }),
    'DELETE /channels/{channel}': reply(204),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function save(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-channel-form"]').trigger('submit')
  await settle()
  await settle()
}

function checked(wrapper: Wrapper, testid: string): boolean {
  return picked(wrapper, testid) === true
}

describe('showroom.channel', () => {
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

  it('loads the channel with its locales, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/channels/{channel}')[0]?.path).toBe('/channels/webshop')
    expect(api).toHaveBeenCalledWith('/screens/showroom.channel', expect.objectContaining({ query: expect.objectContaining({ record: 'webshop' }) }))
    expect(wrapper.find('[data-testid="showroom-channel-title"] h1').text()).toBe('Webshop')
    expect(checked(wrapper, 'showroom-channel-locale-en')).toBe(true)
    expect(checked(wrapper, 'showroom-channel-locale-fr')).toBe(true)
    expect(checked(wrapper, 'showroom-channel-locale-de')).toBe(false)
    expect((wrapper.find('[data-testid="showroom-channel-currencies"]').element as HTMLInputElement).value).toBe('USD, EUR')
    expect(picked(wrapper, 'showroom-channel-tree')).toBe('master')
  })

  it('saves the labels, locales, currencies and tree, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channel-label"]').setValue('Web store')
    await pick(wrapper, 'showroom-channel-locale-fr', false)
    await pick(wrapper, 'showroom-channel-locale-de', true)
    await wrapper.find('[data-testid="showroom-channel-currencies"]').setValue('GBP')
    await pick(wrapper, 'showroom-channel-tree', 'web_tree')
    await save(wrapper)

    const [call] = showroomCalls('PATCH', '/channels/{channel}')
    expect(call?.path).toBe('/channels/webshop')
    expect(call?.body).toEqual({ labels: { en: 'Web store', fr: 'Boutique' }, locales: ['en', 'de'], currencies: ['GBP'], category_tree: 'web_tree' })
    expect(showroomCalls('GET', '/channels/{channel}')).toHaveLength(2)
  })

  it('sends no tree when none is chosen', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await pick(wrapper, 'showroom-channel-tree', null)
    await save(wrapper)

    expect(showroomCalls('PATCH', '/channels/{channel}')[0]?.body).toMatchObject({ category_tree: null })
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /channels/{channel}': invalid({ 'currencies.0': ['The currencies.0 field format is invalid.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channel-currencies"]').setValue('dollars')
    await save(wrapper)

    expect(wrapper.text()).toContain('The currencies.0 field format is invalid.')
  })

  it('deletes the channel once confirmed, then returns to the list', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channel-delete"]').trigger('click')
    await settle()

    const [call] = showroomCalls('DELETE', '/channels/{channel}')
    expect(call?.path).toBe('/channels/webshop')
    expect(call?.body).toBeUndefined()
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/channels'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channel-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/channels/{channel}')).toHaveLength(0)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'PATCH /channels/{channel}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await save(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /channels/{channel}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-channel-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /channels/{channel}': reply(404, { message: 'Not found.' }) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
