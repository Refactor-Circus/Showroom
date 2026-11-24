import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/assets/[asset].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, invalid, mountShowroomPage, ok, resetShowroomPages, screensApi, showroomCalls } from '../catalog/support'
import { hero, pickFile } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `showroom.asset` (AC 54, 71): one asset (`assets.show`) with its file and
 * the records it is linked to. A save (`assets.update`) sends the labels (the
 * current locale's edited, the others kept), as JSON, or as multipart with a
 * replacement file. Links are added (`assets.links.store`) and removed
 * (`assets.links.destroy`, by type, target and role), and delete is
 * confirmed (`assets.destroy`). A 422 shows the field errors, a 403 no
 * access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/showroom/assets/hero'

function showroomWith(overrides: PackageRoutes<'showroom'> = {}): void {
  mockPackageApi('showroom', {
    'GET /assets/{asset}': ok({ data: hero }),
    'PATCH /assets/{asset}': ok({ data: hero }),
    'DELETE /assets/{asset}': reply(204),
    'POST /assets/{asset}/links': ok({ data: hero }),
    'DELETE /assets/{asset}/links': ok({ data: { ...hero, links: [] } }),
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountShowroomPage>>

async function submit(wrapper: Wrapper, form: string): Promise<void> {
  await wrapper.find(`[data-testid="${form}"]`).trigger('submit')
  await settle()
  await settle()
}

describe('showroom.asset', () => {
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

  it('loads the asset with its file details and links, and calls atrium.screens.show with its key and record', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(showroomCalls('GET', '/assets/{asset}')[0]?.path).toBe('/assets/hero')
    expect(api).toHaveBeenCalledWith('/screens/showroom.asset', expect.objectContaining({ query: expect.objectContaining({ record: 'hero' }) }))
    expect(wrapper.find('[data-testid="showroom-asset-title"]').text()).toBe('Hero shot')
    expect(wrapper.find('[data-testid="showroom-asset-image"]').attributes('src')).toBe(hero.url)
    expect(wrapper.find('[data-testid="showroom-asset-open"]').attributes('href')).toBe(hero.url)
    expect(wrapper.find('[data-testid="showroom-asset-size"]').text()).toBe('20.0 KB')
    expect(wrapper.find('[data-testid="showroom-asset-checksum"]').text()).toBe('abc123')

    const product = wrapper.find('[data-testid="showroom-asset-link-product-TEE-1"]')
    expect(product.text()).toContain('image')
    expect(product.find('a').attributes('href')).toBe('/showroom/products?search=TEE-1')
    expect(wrapper.find('[data-testid="showroom-asset-link-owner-acme"]').find('a').attributes('href')).toBe('/showroom/owners?search=acme')
  })

  it('shows the empty state when the asset has no links', async () => {
    showroomWith({ 'GET /assets/{asset}': ok({ data: { ...hero, links: [] } }) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-asset-links-empty"]').exists()).toBe(true)
  })

  it('saves the labels as JSON, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-asset-label"]').setValue('Hero image')
    await submit(wrapper, 'showroom-asset-form')

    const [call] = showroomCalls('PATCH', '/assets/{asset}')
    expect(call?.path).toBe('/assets/hero')
    expect(call?.body).toEqual({ labels: { en: 'Hero image', fr: 'Photo' } })
    expect(showroomCalls('GET', '/assets/{asset}')).toHaveLength(2)
  })

  it('replaces the file with the labels as multipart', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await pickFile(wrapper.find('[data-testid="showroom-asset-file"]'), new File(['png'], 'new.png', { type: 'image/png' }))
    await submit(wrapper, 'showroom-asset-form')

    const [call] = showroomCalls('PATCH', '/assets/{asset}')
    const body = String(call?.body)
    expect(call?.headers.get('content-type')).toContain('multipart/form-data')
    expect(body).toContain('filename="new.png"')
    expect(body).toMatch(/name="labels\[en\]"\r?\n\r?\nHero shot/)
    expect(body).toMatch(/name="labels\[fr\]"\r?\n\r?\nPhoto/)
  })

  it('shows the field errors of a 422 on save', async () => {
    showroomWith({ 'PATCH /assets/{asset}': invalid({ 'labels.en': ['The label may not be greater than 191 characters.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await submit(wrapper, 'showroom-asset-form')

    expect(wrapper.text()).toContain('The label may not be greater than 191 characters.')
  })

  it('links the asset to a record with its type, target and role, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-asset-link-type"]').setValue('product_model')
    await wrapper.find('[data-testid="showroom-asset-link-target"]').setValue('tee')
    await submit(wrapper, 'showroom-asset-link-form')

    const [call] = showroomCalls('POST', '/assets/{asset}/links')
    expect(call?.path).toBe('/assets/hero/links')
    expect(call?.body).toEqual({ type: 'product_model', target: 'tee', role: 'media' })
    expect(showroomCalls('GET', '/assets/{asset}')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on link', async () => {
    showroomWith({ 'POST /assets/{asset}/links': invalid({ target: ['No product has the identifier NOPE.'] }) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-asset-link-target"]').setValue('NOPE')
    await submit(wrapper, 'showroom-asset-link-form')

    expect(wrapper.text()).toContain('No product has the identifier NOPE.')
  })

  it('unlinks a record by its type, target and role, then reloads', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-asset-unlink-product-TEE-1"]').trigger('click')
    await settle()
    await settle()

    const [call] = showroomCalls('DELETE', '/assets/{asset}/links')
    expect(call?.path).toBe('/assets/hero/links')
    expect(call?.query).toEqual({ type: 'product', target: 'TEE-1', role: 'image' })
    expect(showroomCalls('GET', '/assets/{asset}')).toHaveLength(2)
  })

  it('unlinks a record linked without a role, sending no role', async () => {
    showroomWith()

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-asset-unlink-owner-acme"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/assets/{asset}/links')[0]?.query).toEqual({ type: 'owner', target: 'acme' })
  })

  it('deletes the asset once confirmed, then returns to the list', async () => {
    showroomWith()
    confirmWith(true)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-asset-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/assets/{asset}')[0]?.path).toBe('/assets/hero')
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/showroom/assets'))
  })

  it('does not delete when the confirmation is declined', async () => {
    showroomWith()
    confirmWith(false)

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-asset-delete"]').trigger('click')
    await settle()

    expect(showroomCalls('DELETE', '/assets/{asset}')).toHaveLength(0)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    showroomWith({ 'POST /assets/{asset}/links': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)
    await wrapper.find('[data-testid="showroom-asset-link-target"]').setValue('TEE-2')
    await submit(wrapper, 'showroom-asset-link-form')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    showroomWith({ 'GET /assets/{asset}': reply(403) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="showroom-asset-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    showroomWith({ 'GET /assets/{asset}': reply(404, { message: 'Not found.' }) })

    const wrapper = await mountShowroomPage(Page, PATH)

    expect(wrapper.find('[data-testid="showroom-not-found"]').exists()).toBe(true)
  })
})
