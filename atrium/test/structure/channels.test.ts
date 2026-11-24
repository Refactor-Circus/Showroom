import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/channels/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { english, french, german, master, print, web, webshop } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.channels` (AC 54, 71): v1's channels screen, with its locales
 * card. The locales (`locales.index`) are added with a code and a label in
 * the current locale (`locales.store`) and deleted once confirmed
 * (`locales.destroy`). Every channel (`channels.index`) is listed a cursor
 * page at a time, and a new one is created with its code, label, locales,
 * currencies and category tree (`channels.store`; the trees come from
 * `categories.index?roots=1`). A 422 shows the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/channels'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /channels': ok(cursorPage([webshop, print], 'next-cursor')),
    'GET /locales': ok(cursorPage([english, french, german])),
    'GET /categories': ok(cursorPage([master, web])),
    'POST /channels': ok({ data: webshop }, 201),
    'POST /locales': ok({ data: german }, 201),
    'DELETE /locales/{locale}': reply(204),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper, form: string): Promise<void> {
  await wrapper.find(`[data-testid="${form}"]`).trigger('submit')
  await settle()
  await settle()
}

describe('showroom.channels', () => {
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

  it('loads the channels, the locales and the trees, and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/channels')[0]?.query).toEqual({})
    expect(showroomCalls('GET', '/locales')[0]?.query).toEqual({ per_page: '100' })
    expect(showroomCalls('GET', '/categories')[0]?.query).toEqual({ roots: '1', per_page: '100' })
    expect(api).toHaveBeenCalledWith('/screens/showroom.channels', expect.anything())

    const row = wrapper.find('[data-testid="showroom-channel-row-webshop"]')
    expect(row.find('a').attributes('href')).toBe('/showroom/channels/webshop')
    expect(row.text()).toContain('en, fr')
    expect(row.text()).toContain('USD, EUR')
    expect(row.text()).toContain('master')
    expect(wrapper.find('[data-testid="showroom-locale-fr"]').text()).toContain('French')
    expect(wrapper.findAll('[data-testid="showroom-channel-create-tree"] option').map(option => option.attributes('value'))).toEqual(['', 'master', 'web_tree'])
  })

  it('pages the channels with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channels-next"]').trigger('click')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/channels').at(-1)?.query).toEqual({ cursor: 'next-cursor' })
  })

  it('creates a channel with its code, label, locales, currencies and tree, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channel-create-code"]').setValue('mobile')
    await wrapper.find('[data-testid="showroom-channel-create-label"]').setValue('Mobile')
    await wrapper.find('[data-testid="showroom-channel-create-locale-en"]').setValue(true)
    await wrapper.find('[data-testid="showroom-channel-create-locale-de"]').setValue(true)
    await wrapper.find('[data-testid="showroom-channel-create-currencies"]').setValue('USD, EUR, USD')
    await wrapper.find('[data-testid="showroom-channel-create-tree"]').setValue('master')
    await submit(wrapper, 'showroom-channel-create-form')

    const [call] = showroomCalls('POST', '/channels')
    expect(call?.path).toBe('/channels')
    expect(call?.body).toEqual({ code: 'mobile', labels: { en: 'Mobile' }, locales: ['en', 'de'], currencies: ['USD', 'EUR'], category_tree: 'master' })
    expect(showroomCalls('GET', '/channels')).toHaveLength(2)
  })

  it('sends no labels, no currencies and no tree when left blank', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channel-create-code"]').setValue('mobile')
    await wrapper.find('[data-testid="showroom-channel-create-locale-fr"]').setValue(true)
    await submit(wrapper, 'showroom-channel-create-form')

    expect(showroomCalls('POST', '/channels')[0]?.body).toEqual({ code: 'mobile', locales: ['fr'], currencies: [], category_tree: null })
  })

  it('shows the field errors of a 422 on a new channel', async () => {
    showroomWith({ 'POST /channels': invalid({ locales: ['The locales field is required.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channel-create-code"]').setValue('mobile')
    await submit(wrapper, 'showroom-channel-create-form')

    expect(wrapper.find('[data-testid="showroom-channel-new"]').text()).toContain('The locales field is required.')
  })

  it('adds a locale with its code and label, then reloads the locales', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-locale-create-code"]').setValue('nl_BE')
    await wrapper.find('[data-testid="showroom-locale-create-label"]').setValue('Dutch (Belgium)')
    await submit(wrapper, 'showroom-locale-create-form')

    const [call] = showroomCalls('POST', '/locales')
    expect(call?.path).toBe('/locales')
    expect(call?.body).toEqual({ code: 'nl_BE', labels: { en: 'Dutch (Belgium)' } })
    expect(showroomCalls('GET', '/locales')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on a new locale', async () => {
    showroomWith({ 'POST /locales': invalid({ code: ['The code format is invalid.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-locale-create-code"]').setValue('English')
    await submit(wrapper, 'showroom-locale-create-form')

    expect(wrapper.find('[data-testid="showroom-locales"]').text()).toContain('The code format is invalid.')
  })

  it('deletes a locale once confirmed, then reloads the locales', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-locale-delete-de"]').trigger('click')
    await settle()
    await settle()

    const [call] = showroomCalls('DELETE', '/locales/{locale}')
    expect(call?.path).toBe('/locales/de')
    expect(call?.body).toBeUndefined()
    expect(showroomCalls('GET', '/locales')).toHaveLength(2)
  })

  it('does not delete a locale when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-locale-delete-de"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/locales/{locale}')).toHaveLength(0)
  })

  it('shows Showroom\'s message when a locale delete is refused with a 409', async () => {
    showroomWith({ 'DELETE /locales/{locale}': reply(409, { message: 'The locale is still used by a channel.' }) })
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-locale-delete-fr"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="showroom-locales"]').text()).toContain('The locale is still used by a channel.')
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'POST /channels': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-channel-create-code"]').setValue('mobile')
    await submit(wrapper, 'showroom-channel-create-form')

    expect(wrapper.find('[data-testid="showroom-channel-new"] [data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state in the locales card alone when the locales answer 403', async () => {
    showroomWith({ 'GET /locales': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-locales"] [data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-channels-table"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /channels': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-channels-table"]').exists()).toBe(false)
  })
})
