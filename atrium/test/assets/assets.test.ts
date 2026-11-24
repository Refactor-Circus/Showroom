import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/assets/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cursorPage, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { hero, manual, pickFile } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.assets` (AC 54, 71): every asset (`assets.index`), a cursor page
 * at a time, as a grid of tiles or a list, filtered by search, file type and
 * product as v1's filters sent them. An upload (`assets.store`, multipart)
 * sends the file, the code and the label in the current locale. A 422 shows
 * the field errors, a 403 no access.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/assets'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /assets': ok(cursorPage([hero, manual], 'next-cursor')),
    'POST /assets': ok({ data: hero }, 201),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function upload(wrapper: Wrapper): Promise<void> {
  await wrapper.find('[data-testid="showroom-asset-upload-form"]').trigger('submit')
  await settle()
  await settle()
}

describe('showroom.assets', () => {
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

  it('loads the assets as a grid of tiles, and calls atrium.screens.show with its key', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/assets')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/showroom.assets', expect.anything())

    const tile = wrapper.find('[data-testid="showroom-asset-tile-hero"]')
    expect(tile.find('a').attributes('href')).toBe('/showroom/assets/hero')
    expect(tile.find('img').attributes('src')).toBe(hero.url)
    expect(wrapper.find('[data-testid="showroom-asset-tile-manual"]').text()).toContain('pdf')
    expect(wrapper.find('[data-testid="showroom-assets-table"]').exists()).toBe(false)
  })

  it('switches to the list, with each asset\'s label, type and size, without calling Showroom again', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-assets-view-list"]').trigger('click')
    await settle()
    await settle()

    expect(useRouter().currentRoute.value.query).toEqual({ view: 'list' })
    const row = wrapper.find('[data-testid="showroom-asset-row-hero"]')
    expect(row.find('a').attributes('href')).toBe('/showroom/assets/hero')
    expect(row.text()).toContain('Hero shot')
    expect(row.text()).toContain('image/jpeg')
    expect(row.text()).toContain('20.0 KB')
    expect(wrapper.find('[data-testid="showroom-asset-tile-hero"]').exists()).toBe(false)
    expect(showroomCalls('GET', '/assets')).toHaveLength(1)
  })

  it('sends the search, type and product filters as the endpoint\'s query', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-assets-search"]').setValue('hero')
    await wrapper.find('[data-testid="showroom-assets-type"]').setValue('image/*')
    await wrapper.find('[data-testid="showroom-assets-product"]').setValue('TEE-1')
    await wrapper.find('[data-testid="showroom-assets-filters"]').trigger('submit')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/assets').at(-1)?.query).toEqual({ search: 'hero', type: 'image/*', product: 'TEE-1' })
  })

  it('pages with the endpoint\'s cursor', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-assets-next"]').trigger('click')
    await settle()
    await settle()

    expect(showroomCalls('GET', '/assets').at(-1)?.query).toEqual({ cursor: 'next-cursor' })
  })

  it('uploads a file with its code and label as multipart, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await pickFile(wrapper.find('[data-testid="showroom-asset-upload-file"]'), new File(['jpeg'], 'front.jpg', { type: 'image/jpeg' }))
    await wrapper.find('[data-testid="showroom-asset-upload-code"]').setValue('front')
    await wrapper.find('[data-testid="showroom-asset-upload-label"]').setValue('Front view')
    await upload(wrapper)

    const [call] = showroomCalls('POST', '/assets')
    const body = String(call?.body)
    expect(call?.headers.get('content-type')).toContain('multipart/form-data')
    expect(body).toContain('filename="front.jpg"')
    expect(body).toMatch(/name="code"\r?\n\r?\nfront/)
    expect(body).toMatch(/name="labels\[en\]"\r?\n\r?\nFront view/)
    expect(showroomCalls('GET', '/assets')).toHaveLength(2)
  })

  it('leaves out a blank code and label, so Showroom names the asset after the file', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await pickFile(wrapper.find('[data-testid="showroom-asset-upload-file"]'), new File(['pdf'], 'manual.pdf', { type: 'application/pdf' }))
    await upload(wrapper)

    const body = String(showroomCalls('POST', '/assets')[0]?.body)
    expect(body).toContain('filename="manual.pdf"')
    expect(body).not.toContain('name="code"')
    expect(body).not.toContain('name="labels')
  })

  it('shows the field errors of a 422 on upload', async () => {
    showroomWith({ 'POST /assets': invalid({ file: ['The file field must not be greater than 51200 kilobytes.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await pickFile(wrapper.find('[data-testid="showroom-asset-upload-file"]'), new File(['x'], 'big.png', { type: 'image/png' }))
    await upload(wrapper)

    expect(wrapper.text()).toContain('The file field must not be greater than 51200 kilobytes.')
    expect(showroomCalls('GET', '/assets')).toHaveLength(1)
  })

  it('shows the no-access notice when an upload answers 403', async () => {
    showroomWith({ 'POST /assets': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await pickFile(wrapper.find('[data-testid="showroom-asset-upload-file"]'), new File(['x'], 'a.png', { type: 'image/png' }))
    await upload(wrapper)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /assets': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-asset-tile-hero"]').exists()).toBe(false)
  })
})
